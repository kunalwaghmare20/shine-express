import { Permission, UserRole, hasPermission } from "@/config/roles";
import { requireAuth, ForbiddenError } from "@/lib/auth";
import { getDb } from "@/lib/db";
import { customers, employees } from "@/lib/db/schema";
import { and, eq, isNull } from "drizzle-orm";
import type { UserRecord } from "@/types/database";

export interface BookingAccessContext {
  user: UserRecord;
  customerId?: string;
  employeeId?: string;
  branchScope?: string;
  canManageAll: boolean;
  canManageBranch: boolean;
  canUpdateJob: boolean;
}

export async function requireBookingAccess(): Promise<BookingAccessContext> {
  const user = await requireAuth();
  const role = user.role as UserRole;
  const db = getDb();

  const canManageAll = hasPermission(role, Permission.MANAGE_ALL_BOOKINGS);
  const canManageBranch = hasPermission(role, Permission.MANAGE_BRANCH_BOOKINGS);
  const canUpdateJob = hasPermission(role, Permission.UPDATE_JOB_STATUS);
  const canCreate = hasPermission(role, Permission.CREATE_BOOKING);

  if (!canManageAll && !canManageBranch && !canUpdateJob && !canCreate) {
    throw new ForbiddenError("Insufficient permissions for bookings");
  }

  let customerId: string | undefined;
  let employeeId: string | undefined;
  let branchScope: string | undefined;

  if (role === UserRole.CUSTOMER) {
    const customer = await db.query.customers.findFirst({
      where: and(eq(customers.userId, user.id), isNull(customers.deletedAt)),
    });
    customerId = customer?.id;
  }

  if (
    role === UserRole.BRANCH_MANAGER ||
    role === UserRole.SERVICE_STAFF
  ) {
    const employee = await db.query.employees.findFirst({
      where: and(eq(employees.userId, user.id), isNull(employees.deletedAt)),
    });
    employeeId = employee?.id;
    branchScope = employee?.branchId;
  }

  return {
    user,
    customerId,
    employeeId,
    branchScope,
    canManageAll,
    canManageBranch,
    canUpdateJob,
  };
}

export async function requireBookingManageAccess(): Promise<BookingAccessContext> {
  const ctx = await requireBookingAccess();
  if (!ctx.canManageAll && !ctx.canManageBranch) {
    throw new ForbiddenError("Insufficient permissions to manage bookings");
  }
  return ctx;
}
