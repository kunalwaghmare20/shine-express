import { Permission, UserRole, hasPermission } from "@/config/roles";
import { requireAuth, ForbiddenError } from "@/lib/auth";
import type { UserRecord } from "@/types/database";

/** Public/catalog read — any authenticated staff/admin, or Super Admin */
export async function requireServiceReadAccess(): Promise<UserRecord> {
  const user = await requireAuth();
  const role = user.role as UserRole;

  if (
    role === UserRole.SUPER_ADMIN ||
    role === UserRole.BRANCH_MANAGER ||
    role === UserRole.SERVICE_STAFF ||
    role === UserRole.CUSTOMER ||
    hasPermission(role, Permission.MANAGE_SERVICES)
  ) {
    return user;
  }

  throw new ForbiddenError("Insufficient permissions to view services");
}

export async function requireServiceWriteAccess(): Promise<UserRecord> {
  const user = await requireAuth();
  const role = user.role as UserRole;

  if (hasPermission(role, Permission.MANAGE_SERVICES)) {
    return user;
  }

  throw new ForbiddenError("Insufficient permissions to manage services");
}
