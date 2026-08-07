import {
  and,
  count,
  desc,
  eq,
  gte,
  lt,
  sql,
  sum,
} from "drizzle-orm";
import { getDb } from "@/lib/db";
import {
  bookingAssignments,
  bookings,
  customers,
  employees,
  payments,
  services,
  users,
} from "@/lib/db/schema";
import type { ReportPeriod, ReportsQuery } from "@/features/reports/validators/reports.schema";
import type { ReportsData } from "@/server/dto/reports.dto";
import {
  formatMonthLabel,
  formatShortDate,
  toDecimalNumber,
} from "@/lib/utils/format";

function startOfDay(date: Date): Date {
  const d = new Date(date);
  d.setHours(0, 0, 0, 0);
  return d;
}

function addDays(date: Date, days: number): Date {
  const d = new Date(date);
  d.setDate(d.getDate() + days);
  return d;
}

function resolveRange(period: ReportPeriod, anchor = new Date()) {
  const endExclusive = addDays(startOfDay(anchor), 1);

  if (period === "daily") {
    const start = startOfDay(anchor);
    return {
      period,
      label: `Daily · ${formatShortDate(start)}`,
      start,
      end: endExclusive,
      bucket: "hour" as const,
    };
  }

  if (period === "weekly") {
    const start = addDays(startOfDay(anchor), -6);
    return {
      period,
      label: `Weekly · ${formatShortDate(start)} – ${formatShortDate(anchor)}`,
      start,
      end: endExclusive,
      bucket: "day" as const,
    };
  }

  if (period === "monthly") {
    const start = new Date(anchor.getFullYear(), anchor.getMonth(), 1);
    return {
      period,
      label: `Monthly · ${formatMonthLabel(
        `${anchor.getFullYear()}-${String(anchor.getMonth() + 1).padStart(2, "0")}`
      )}`,
      start,
      end: endExclusive,
      bucket: "day" as const,
    };
  }

  const start = new Date(anchor.getFullYear(), 0, 1);
  return {
    period,
    label: `Yearly · ${anchor.getFullYear()}`,
    start,
    end: endExclusive,
    bucket: "month" as const,
  };
}

/**
 * Aggregates operational reports for admin / branch manager.
 */
export async function getReportsData(
  query: ReportsQuery,
  branchScope?: string
): Promise<ReportsData> {
  const db = getDb();
  const anchor = query.date ? new Date(query.date) : new Date();
  const range = resolveRange(query.period, anchor);
  const branchId = branchScope ?? query.branchId;

  const bookingBranchFilter = branchId
    ? eq(bookings.branchId, branchId)
    : undefined;
  const paymentBranchFilter = branchId
    ? sql`${payments.bookingId} IN (SELECT id FROM bookings WHERE branch_id = ${branchId})`
    : undefined;
  const employeeBranchFilter = branchId
    ? eq(employees.branchId, branchId)
    : undefined;

  const inRangeBookings = and(
    gte(bookings.createdAt, range.start),
    lt(bookings.createdAt, range.end),
    bookingBranchFilter
  );

  const inRangePayments = and(
    eq(payments.status, "COMPLETED"),
    gte(payments.paidAt, range.start),
    lt(payments.paidAt, range.end),
    paymentBranchFilter
  );

  const [
    totalBookingsResult,
    completedResult,
    cancelledResult,
    revenueResult,
    newCustomersResult,
    statusRows,
    revenueTrendRows,
    serviceRows,
    employeeRows,
    customerGrowthRows,
  ] = await Promise.all([
    db.select({ value: count() }).from(bookings).where(inRangeBookings),

    db
      .select({ value: count() })
      .from(bookings)
      .where(
        and(
          eq(bookings.status, "COMPLETED"),
          gte(bookings.completedAt, range.start),
          lt(bookings.completedAt, range.end),
          bookingBranchFilter
        )
      ),

    db
      .select({ value: count() })
      .from(bookings)
      .where(
        and(
          eq(bookings.status, "CANCELLED"),
          gte(bookings.cancelledAt, range.start),
          lt(bookings.cancelledAt, range.end),
          bookingBranchFilter
        )
      ),

    db
      .select({ value: sum(payments.amount) })
      .from(payments)
      .where(inRangePayments),

    db
      .select({ value: count() })
      .from(customers)
      .where(
        and(
          gte(customers.createdAt, range.start),
          lt(customers.createdAt, range.end)
        )
      ),

    db
      .select({
        status: bookings.status,
        count: count(bookings.id),
      })
      .from(bookings)
      .where(inRangeBookings)
      .groupBy(bookings.status),

    range.bucket === "month"
      ? db
          .select({
            key: sql<string>`DATE_FORMAT(${payments.paidAt}, '%Y-%m')`.as("key"),
            revenue: sum(payments.amount).as("revenue"),
            bookings: count(payments.id).as("bookings"),
          })
          .from(payments)
          .where(inRangePayments)
          .groupBy(sql`DATE_FORMAT(${payments.paidAt}, '%Y-%m')`)
          .orderBy(sql`DATE_FORMAT(${payments.paidAt}, '%Y-%m')`)
      : range.bucket === "hour"
        ? db
            .select({
              key: sql<string>`DATE_FORMAT(${payments.paidAt}, '%H:00')`.as("key"),
              revenue: sum(payments.amount).as("revenue"),
              bookings: count(payments.id).as("bookings"),
            })
            .from(payments)
            .where(inRangePayments)
            .groupBy(sql`DATE_FORMAT(${payments.paidAt}, '%H:00')`)
            .orderBy(sql`DATE_FORMAT(${payments.paidAt}, '%H:00')`)
        : db
            .select({
              key: sql<string>`DATE_FORMAT(${payments.paidAt}, '%Y-%m-%d')`.as(
                "key"
              ),
              revenue: sum(payments.amount).as("revenue"),
              bookings: count(payments.id).as("bookings"),
            })
            .from(payments)
            .where(inRangePayments)
            .groupBy(sql`DATE_FORMAT(${payments.paidAt}, '%Y-%m-%d')`)
            .orderBy(sql`DATE_FORMAT(${payments.paidAt}, '%Y-%m-%d')`),

    db
      .select({
        serviceId: services.id,
        serviceName: services.name,
        bookings: count(bookings.id).as("bookings"),
        revenue: sum(bookings.totalAmount).as("revenue"),
      })
      .from(bookings)
      .innerJoin(services, eq(bookings.serviceId, services.id))
      .where(inRangeBookings)
      .groupBy(services.id, services.name)
      .orderBy(desc(count(bookings.id)))
      .limit(10),

    db
      .select({
        employeeId: employees.id,
        employeeCode: employees.employeeCode,
        firstName: users.firstName,
        lastName: users.lastName,
        jobsAssigned: count(bookingAssignments.id).as("jobs_assigned"),
        jobsCompleted: sql<number>`SUM(CASE WHEN ${bookings.status} = 'COMPLETED' THEN 1 ELSE 0 END)`.as(
          "jobs_completed"
        ),
      })
      .from(bookingAssignments)
      .innerJoin(employees, eq(bookingAssignments.employeeId, employees.id))
      .innerJoin(users, eq(employees.userId, users.id))
      .innerJoin(bookings, eq(bookingAssignments.bookingId, bookings.id))
      .where(
        and(
          gte(bookings.createdAt, range.start),
          lt(bookings.createdAt, range.end),
          employeeBranchFilter
        )
      )
      .groupBy(
        employees.id,
        employees.employeeCode,
        users.firstName,
        users.lastName
      )
      .orderBy(desc(count(bookingAssignments.id)))
      .limit(10),

    range.bucket === "month"
      ? db
          .select({
            key: sql<string>`DATE_FORMAT(${customers.createdAt}, '%Y-%m')`.as(
              "key"
            ),
            newCustomers: count(customers.id).as("new_customers"),
          })
          .from(customers)
          .where(
            and(
              gte(customers.createdAt, range.start),
              lt(customers.createdAt, range.end)
            )
          )
          .groupBy(sql`DATE_FORMAT(${customers.createdAt}, '%Y-%m')`)
          .orderBy(sql`DATE_FORMAT(${customers.createdAt}, '%Y-%m')`)
      : db
          .select({
            key: sql<string>`DATE_FORMAT(${customers.createdAt}, '%Y-%m-%d')`.as(
              "key"
            ),
            newCustomers: count(customers.id).as("new_customers"),
          })
          .from(customers)
          .where(
            and(
              gte(customers.createdAt, range.start),
              lt(customers.createdAt, range.end)
            )
          )
          .groupBy(sql`DATE_FORMAT(${customers.createdAt}, '%Y-%m-%d')`)
          .orderBy(sql`DATE_FORMAT(${customers.createdAt}, '%Y-%m-%d')`),
  ]);

  const revenue = toDecimalNumber(revenueResult[0]?.value);
  const completedBookings = completedResult[0]?.value ?? 0;

  return {
    range: {
      period: range.period,
      label: range.label,
      start: range.start.toISOString(),
      end: range.end.toISOString(),
    },
    summary: {
      totalBookings: totalBookingsResult[0]?.value ?? 0,
      completedBookings,
      cancelledBookings: cancelledResult[0]?.value ?? 0,
      revenue,
      newCustomers: newCustomersResult[0]?.value ?? 0,
      averageTicket:
        completedBookings > 0
          ? Math.round((revenue / completedBookings) * 100) / 100
          : 0,
    },
    revenueTrend: revenueTrendRows.map((row) => ({
      key: row.key,
      label:
        range.bucket === "month"
          ? formatMonthLabel(row.key)
          : range.bucket === "hour"
            ? row.key
            : formatShortDate(new Date(row.key)),
      revenue: toDecimalNumber(row.revenue),
      bookings: Number(row.bookings),
    })),
    servicePopularity: serviceRows.map((row) => ({
      serviceId: row.serviceId,
      serviceName: row.serviceName,
      bookings: Number(row.bookings),
      revenue: toDecimalNumber(row.revenue),
    })),
    employeePerformance: employeeRows.map((row) => {
      const assigned = Number(row.jobsAssigned);
      const completed = Number(row.jobsCompleted);
      return {
        employeeId: row.employeeId,
        employeeName: `${row.firstName} ${row.lastName}`.trim(),
        employeeCode: row.employeeCode,
        jobsAssigned: assigned,
        jobsCompleted: completed,
        completionRate:
          assigned > 0 ? Math.round((completed / assigned) * 100) : 0,
      };
    }),
    customerGrowth: customerGrowthRows.map((row) => ({
      key: row.key,
      label:
        range.bucket === "month"
          ? formatMonthLabel(row.key)
          : formatShortDate(new Date(row.key)),
      newCustomers: Number(row.newCustomers),
    })),
    statusBreakdown: statusRows
      .map((row) => ({
        status: row.status,
        count: Number(row.count),
      }))
      .sort((a, b) => b.count - a.count),
  };
}
