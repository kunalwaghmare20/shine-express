import { Permission, UserRole, hasPermission } from "@/config/roles";
import { requireAuth, ForbiddenError } from "@/lib/auth";
import { getDb } from "@/lib/db";
import { employees } from "@/lib/db/schema";
import { eq, and, isNull } from "drizzle-orm";
import type { UserRecord } from "@/types/database";

export interface EmployeeAccessContext {
  user: UserRecord;
  /** When set, operations are scoped to this branch only */
  branchScope?: string;
  canWrite: boolean;
}

export async function requireEmployeeReadAccess(): Promise<EmployeeAccessContext> {
  const user = await requireAuth();
  const role = user.role as UserRole;

  if (hasPermission(role, Permission.MANAGE_ALL_EMPLOYEES)) {
    return { user, canWrite: true };
  }

  if (
    hasPermission(role, Permission.MANAGE_BRANCH_EMPLOYEES) ||
    hasPermission(role, Permission.VIEW_EMPLOYEES)
  ) {
    const branchScope = await resolveManagerBranchId(user.id);
    return {
      user,
      branchScope,
      canWrite: hasPermission(role, Permission.MANAGE_BRANCH_EMPLOYEES),
    };
  }

  throw new ForbiddenError("Insufficient permissions to view employees");
}

export async function requireEmployeeWriteAccess(
  targetBranchId?: string
): Promise<EmployeeAccessContext> {
  const user = await requireAuth();
  const role = user.role as UserRole;

  if (hasPermission(role, Permission.MANAGE_ALL_EMPLOYEES)) {
    return { user, canWrite: true };
  }

  if (hasPermission(role, Permission.MANAGE_BRANCH_EMPLOYEES)) {
    const branchScope = await resolveManagerBranchId(user.id);
    if (!branchScope) {
      throw new ForbiddenError("Branch not assigned to your account");
    }
    if (targetBranchId && targetBranchId !== branchScope) {
      throw new ForbiddenError("Cannot manage employees outside your branch");
    }
    return { user, branchScope, canWrite: true };
  }

  throw new ForbiddenError("Insufficient permissions to manage employees");
}

async function resolveManagerBranchId(userId: string): Promise<string | undefined> {
  const db = getDb();
  const record = await db.query.employees.findFirst({
    where: and(eq(employees.userId, userId), isNull(employees.deletedAt)),
  });
  return record?.branchId;
}

export function assertBranchAccess(
  ctx: EmployeeAccessContext,
  branchId: string
): void {
  if (ctx.branchScope && ctx.branchScope !== branchId) {
    throw new ForbiddenError("Access denied for this branch");
  }
}
