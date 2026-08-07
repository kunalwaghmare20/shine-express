import { auth, currentUser } from "@clerk/nextjs/server";
import { eq } from "drizzle-orm";
import { getDb } from "@/lib/db";
import { users } from "@/lib/db/schema";
import type { UserRecord } from "@/types/database";
import { UserRole, Permission, hasPermission } from "@/config/roles";
import { parseUserRole, syncUserFromClerk } from "./sync-user";

export class AuthError extends Error {
  constructor(
    message: string,
    public statusCode: number = 401
  ) {
    super(message);
    this.name = "AuthError";
  }
}

export class ForbiddenError extends AuthError {
  constructor(message = "Forbidden") {
    super(message, 403);
    this.name = "ForbiddenError";
  }
}

/**
 * Returns the authenticated user from MySQL, synced from Clerk if needed.
 */
export async function getCurrentUser(): Promise<UserRecord | null> {
  const { userId } = await auth();
  if (!userId) return null;

  const db = getDb();
  let user = await db.query.users.findFirst({
    where: eq(users.clerkId, userId),
  });

  if (user) return user;

  const clerkUser = await currentUser();
  if (!clerkUser) return null;

  const role = parseUserRole(clerkUser.publicMetadata?.role);

  user = await syncUserFromClerk({
    clerkId: userId,
    email: clerkUser.emailAddresses[0]?.emailAddress ?? "",
    firstName: clerkUser.firstName ?? "User",
    lastName: clerkUser.lastName ?? "",
    phone: clerkUser.phoneNumbers[0]?.phoneNumber,
    avatarUrl: clerkUser.imageUrl,
    role,
  });

  return user;
}

export async function requireAuth(): Promise<UserRecord> {
  const user = await getCurrentUser();
  if (!user || !user.isActive || user.deletedAt) {
    throw new AuthError("Unauthorized");
  }
  return user;
}

export async function requireRole(...roles: UserRole[]): Promise<UserRecord> {
  const user = await requireAuth();
  if (!roles.includes(user.role as UserRole)) {
    throw new ForbiddenError("Insufficient role");
  }
  return user;
}

export async function requirePermission(
  permission: Permission
): Promise<UserRecord> {
  const user = await requireAuth();
  if (!hasPermission(user.role as UserRole, permission)) {
    throw new ForbiddenError("Insufficient permissions");
  }
  return user;
}

export function getUserDisplayName(user: UserRecord): string {
  return `${user.firstName} ${user.lastName}`.trim();
}
