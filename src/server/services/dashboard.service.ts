import {
  and,
  count,
  desc,
  eq,
  gte,
  inArray,
  isNull,
  lt,
  sql,
  sum,
} from "drizzle-orm";
import { getDb } from "@/lib/db";
import {
  bookings,
  customers,
  employees,
  payments,
  services,
  users,
} from "@/lib/db/schema";
import type { AdminDashboardData } from "@/server/dto/dashboard.dto";
import {
  formatCurrency,
  formatMonthLabel,
  formatNumber,
  formatShortDate,
  toDecimalNumber,
} from "@/lib/utils/format";

const PENDING_STATUSES = [
  "PENDING",
  "CONFIRMED",
  "ASSIGNED",
  "ACCEPTED",
  "ON_THE_WAY",
  "STARTED",
] as const;

function startOfDay(date = new Date()): Date {
  const d = new Date(date);
  d.setHours(0, 0, 0, 0);
  return d;
}

function startOfMonth(date = new Date()): Date {
  return new Date(date.getFullYear(), date.getMonth(), 1);
}

function addDays(date: Date, days: number): Date {
  const d = new Date(date);
  d.setDate(d.getDate() + days);
  return d;
}

/**
 * Aggregates admin dashboard metrics from MySQL via Drizzle.
 */
export async function getAdminDashboardData(): Promise<AdminDashboardData> {
  const db = getDb();
  const today = startOfDay();
  const tomorrow = addDays(today, 1);
  const monthStart = startOfMonth();
  const trendStart = addDays(today, -6);

  const [
    todayBookingsResult,
    pendingJobsResult,
    completedTodayResult,
    revenueMonthResult,
    customersResult,
    employeesResult,
    monthlyRevenueRows,
    trendRows,
    topServiceRows,
    recentRows,
  ] = await Promise.all([
    db
      .select({ value: count() })
      .from(bookings)
      .where(
        and(
          gte(bookings.scheduledDate, today),
          lt(bookings.scheduledDate, tomorrow)
        )
      ),

    db
      .select({ value: count() })
      .from(bookings)
      .where(inArray(bookings.status, [...PENDING_STATUSES])),

    db
      .select({ value: count() })
      .from(bookings)
      .where(
        and(
          eq(bookings.status, "COMPLETED"),
          gte(bookings.completedAt, today),
          lt(bookings.completedAt, tomorrow)
        )
      ),

    db
      .select({ value: sum(payments.amount) })
      .from(payments)
      .where(
        and(
          eq(payments.status, "COMPLETED"),
          gte(payments.paidAt, monthStart)
        )
      ),

    db
      .select({ value: count() })
      .from(customers)
      .where(isNull(customers.deletedAt)),

    db
      .select({ value: count() })
      .from(employees)
      .where(isNull(employees.deletedAt)),

    db
      .select({
        month: sql<string>`DATE_FORMAT(${payments.paidAt}, '%Y-%m')`.as("month"),
        revenue: sum(payments.amount).as("revenue"),
      })
      .from(payments)
      .where(
        and(
          eq(payments.status, "COMPLETED"),
          gte(
            payments.paidAt,
            new Date(today.getFullYear(), today.getMonth() - 5, 1)
          )
        )
      )
      .groupBy(sql`DATE_FORMAT(${payments.paidAt}, '%Y-%m')`)
      .orderBy(sql`DATE_FORMAT(${payments.paidAt}, '%Y-%m')`),

    db
      .select({
        date: sql<string>`DATE_FORMAT(${bookings.createdAt}, '%Y-%m-%d')`.as(
          "date"
        ),
        bookings: count(bookings.id).as("bookings"),
      })
      .from(bookings)
      .where(gte(bookings.createdAt, trendStart))
      .groupBy(sql`DATE_FORMAT(${bookings.createdAt}, '%Y-%m-%d')`)
      .orderBy(sql`DATE_FORMAT(${bookings.createdAt}, '%Y-%m-%d')`),

    db
      .select({
        serviceId: services.id,
        serviceName: services.name,
        bookings: count(bookings.id).as("bookings"),
      })
      .from(bookings)
      .innerJoin(services, eq(bookings.serviceId, services.id))
      .groupBy(services.id, services.name)
      .orderBy(desc(count(bookings.id)))
      .limit(5),

    db
      .select({
        id: bookings.id,
        bookingNumber: bookings.bookingNumber,
        customerFirstName: users.firstName,
        customerLastName: users.lastName,
        serviceName: services.name,
        status: bookings.status,
        scheduledDate: bookings.scheduledDate,
        totalAmount: bookings.totalAmount,
      })
      .from(bookings)
      .innerJoin(customers, eq(bookings.customerId, customers.id))
      .innerJoin(users, eq(customers.userId, users.id))
      .innerJoin(services, eq(bookings.serviceId, services.id))
      .orderBy(desc(bookings.createdAt))
      .limit(8),
  ]);

  const revenueMonth = toDecimalNumber(revenueMonthResult[0]?.value);

  const stats = [
    {
      key: "todayBookings",
      label: "Today's Bookings",
      value: todayBookingsResult[0]?.value ?? 0,
      formattedValue: formatNumber(todayBookingsResult[0]?.value ?? 0),
    },
    {
      key: "pendingJobs",
      label: "Pending Jobs",
      value: pendingJobsResult[0]?.value ?? 0,
      formattedValue: formatNumber(pendingJobsResult[0]?.value ?? 0),
    },
    {
      key: "completedToday",
      label: "Completed Today",
      value: completedTodayResult[0]?.value ?? 0,
      formattedValue: formatNumber(completedTodayResult[0]?.value ?? 0),
    },
    {
      key: "revenueMonth",
      label: "Revenue (This Month)",
      value: revenueMonth,
      formattedValue: formatCurrency(revenueMonth),
    },
    {
      key: "customers",
      label: "Customers",
      value: customersResult[0]?.value ?? 0,
      formattedValue: formatNumber(customersResult[0]?.value ?? 0),
    },
    {
      key: "employees",
      label: "Employees",
      value: employeesResult[0]?.value ?? 0,
      formattedValue: formatNumber(employeesResult[0]?.value ?? 0),
    },
  ];

  // Fill missing days in booking trends (last 7 days)
  const trendMap = new Map(
    trendRows.map((row) => [row.date, Number(row.bookings)])
  );
  const bookingTrends = Array.from({ length: 7 }, (_, i) => {
    const date = addDays(trendStart, i);
    const key = date.toISOString().slice(0, 10);
    return {
      date: key,
      label: formatShortDate(date),
      bookings: trendMap.get(key) ?? 0,
    };
  });

  return {
    stats,
    monthlyRevenue: monthlyRevenueRows.map((row) => ({
      month: row.month,
      label: formatMonthLabel(row.month),
      revenue: toDecimalNumber(row.revenue),
    })),
    bookingTrends,
    topServices: topServiceRows.map((row) => ({
      serviceId: row.serviceId,
      serviceName: row.serviceName,
      bookings: Number(row.bookings),
    })),
    recentBookings: recentRows.map((row) => ({
      id: row.id,
      bookingNumber: row.bookingNumber,
      customerName: `${row.customerFirstName} ${row.customerLastName}`.trim(),
      serviceName: row.serviceName,
      status: row.status,
      scheduledDate: row.scheduledDate.toISOString().slice(0, 10),
      totalAmount: toDecimalNumber(row.totalAmount),
    })),
  };
}
