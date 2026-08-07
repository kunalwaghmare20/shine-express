import { eq } from "drizzle-orm";
import { getDb } from "@/lib/db";
import { users, customers } from "@/lib/db/schema";
import type { UserRecord } from "@/types/database";
import { UserRole, ROLE_ROUTE_PREFIX } from "@/config/roles";

export interface SyncUserInput {
  clerkId: string;
  email: string;
  firstName: string;
  lastName: string;
  phone?: string | null;
  avatarUrl?: string | null;
  role?: UserRole;
}

/**
 * Creates or updates a user in MySQL when Clerk fires a webhook event.
 */
export async function syncUserFromClerk(
  input: SyncUserInput
): Promise<UserRecord> {
  const db = getDb();
  const role = input.role ?? UserRole.CUSTOMER;

  const existing = await db.query.users.findFirst({
    where: eq(users.clerkId, input.clerkId),
  });

  if (existing) {
    await db
      .update(users)
      .set({
        email: input.email,
        firstName: input.firstName,
        lastName: input.lastName,
        phone: input.phone ?? existing.phone,
        avatarUrl: input.avatarUrl ?? existing.avatarUrl,
        lastLoginAt: new Date(),
      })
      .where(eq(users.id, existing.id));

    const updated = await db.query.users.findFirst({
      where: eq(users.id, existing.id),
    });

    return updated!;
  }

  const byEmail = await db.query.users.findFirst({
    where: eq(users.email, input.email),
  });

  if (byEmail) {
    await db
      .update(users)
      .set({
        clerkId: input.clerkId,
        firstName: input.firstName,
        lastName: input.lastName,
        phone: input.phone ?? byEmail.phone,
        avatarUrl: input.avatarUrl ?? byEmail.avatarUrl,
        lastLoginAt: new Date(),
      })
      .where(eq(users.id, byEmail.id));

    const updated = await db.query.users.findFirst({
      where: eq(users.id, byEmail.id),
    });

    return updated!;
  }

  const [inserted] = await db
    .insert(users)
    .values({
      clerkId: input.clerkId,
      email: input.email,
      firstName: input.firstName,
      lastName: input.lastName,
      phone: input.phone,
      avatarUrl: input.avatarUrl,
      role,
      lastLoginAt: new Date(),
    })
    .$returningId();

  if (role === UserRole.CUSTOMER) {
    await db.insert(customers).values({ userId: inserted.id });
  }

  const created = await db.query.users.findFirst({
    where: eq(users.id, inserted.id),
  });

  return created!;
}

export async function deleteUserByClerkId(clerkId: string): Promise<void> {
  const db = getDb();
  await db
    .update(users)
    .set({ deletedAt: new Date(), isActive: false })
    .where(eq(users.clerkId, clerkId));
}

export function getDefaultRouteForRole(role: UserRole): string {
  return ROLE_ROUTE_PREFIX[role] ?? "/book";
}

export function parseUserRole(value: unknown): UserRole {
  if (
    typeof value === "string" &&
    Object.values(UserRole).includes(value as UserRole)
  ) {
    return value as UserRole;
  }

  return UserRole.CUSTOMER;
}
