import { Permission, hasPermission } from "@/config/roles";
import { requireAuth, ForbiddenError } from "@/lib/auth";
import type { UserRecord } from "@/types/database";
import { UserRole } from "@/config/roles";

export async function requireCustomerReadAccess(): Promise<UserRecord> {
  const user = await requireAuth();
  const role = user.role as UserRole;

  if (
    hasPermission(role, Permission.MANAGE_ALL_CUSTOMERS) ||
    hasPermission(role, Permission.VIEW_CUSTOMERS)
  ) {
    return user;
  }

  throw new ForbiddenError("Insufficient permissions to view customers");
}

export async function requireCustomerWriteAccess(): Promise<UserRecord> {
  const user = await requireAuth();
  const role = user.role as UserRole;

  if (hasPermission(role, Permission.MANAGE_ALL_CUSTOMERS)) {
    return user;
  }

  throw new ForbiddenError("Insufficient permissions to manage customers");
}
