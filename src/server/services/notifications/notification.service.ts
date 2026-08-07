import { and, count, desc, eq } from "drizzle-orm";
import { getDb } from "@/lib/db";
import { notifications, users } from "@/lib/db/schema";
import { inAppProvider } from "./providers/in-app";
import { emailProvider } from "./providers/email";
import { smsProvider } from "./providers/sms";
import { whatsappProvider } from "./providers/whatsapp";
import type {
  NotificationChannel,
  NotificationPayload,
  NotificationType,
} from "./types";
import type { PaginatedResult } from "@/server/dto/pagination.dto";

export class NotificationServiceError extends Error {
  constructor(
    message: string,
    public statusCode: number = 400
  ) {
    super(message);
  }
}

export interface NotificationDto {
  id: string;
  title: string;
  body: string;
  type: string;
  channel: string;
  isRead: boolean;
  metadata: Record<string, unknown>;
  createdAt: string;
  readAt: string | null;
}

export interface CreateNotificationInput {
  userId: string;
  title: string;
  body: string;
  type?: NotificationType;
  metadata?: Record<string, unknown>;
  /** Channels to deliver on. Defaults to IN_APP + EMAIL */
  channels?: NotificationChannel[];
}

const providers = {
  IN_APP: inAppProvider,
  EMAIL: emailProvider,
  SMS: smsProvider,
  WHATSAPP: whatsappProvider,
} as const;

/**
 * Creates an in-app notification and fans out to optional external channels.
 */
export async function notify(
  input: CreateNotificationInput
): Promise<NotificationDto> {
  const db = getDb();
  const channels = input.channels ?? ["IN_APP", "EMAIL"];
  const type = input.type ?? "GENERAL";

  const user = await db.query.users.findFirst({
    where: eq(users.id, input.userId),
  });

  if (!user) {
    throw new NotificationServiceError("User not found", 404);
  }

  const payload: NotificationPayload = {
    userId: input.userId,
    title: input.title,
    body: input.body,
    type,
    metadata: input.metadata,
    email: user.email,
    phone: user.phone,
  };

  // Always persist in-app record when IN_APP is requested (default)
  const [inserted] = await db
    .insert(notifications)
    .values({
      userId: input.userId,
      title: input.title,
      body: input.body,
      type,
      channel: "IN_APP",
      metadata: input.metadata ?? {},
      sentAt: new Date(),
    })
    .$returningId();

  // Fan-out external channels (non-blocking failures)
  await Promise.allSettled(
    channels
      .filter((c) => c !== "IN_APP")
      .map(async (channel) => {
        const provider = providers[channel];
        const result = await provider.send(payload);
        if (!result.success) {
          console.error(
            `[notification:${channel}] failed for user=${input.userId}:`,
            result.error
          );
        }
        return result;
      })
  );

  const row = await db.query.notifications.findFirst({
    where: eq(notifications.id, inserted.id),
  });

  return mapNotification(row!);
}

export async function notifyMany(
  inputs: CreateNotificationInput[]
): Promise<void> {
  await Promise.allSettled(inputs.map((input) => notify(input)));
}

export async function listNotifications(
  userId: string,
  page = 1,
  limit = 20,
  unreadOnly = false
): Promise<PaginatedResult<NotificationDto>> {
  const db = getDb();
  const offset = (page - 1) * limit;

  const whereClause = and(
    eq(notifications.userId, userId),
    unreadOnly ? eq(notifications.isRead, false) : undefined
  );

  const [rows, totalResult] = await Promise.all([
    db
      .select()
      .from(notifications)
      .where(whereClause)
      .orderBy(desc(notifications.createdAt))
      .limit(limit)
      .offset(offset),
    db.select({ value: count() }).from(notifications).where(whereClause),
  ]);

  const total = totalResult[0]?.value ?? 0;

  return {
    items: rows.map(mapNotification),
    page,
    limit,
    total,
    totalPages: Math.ceil(total / limit) || 1,
  };
}

export async function getUnreadCount(userId: string): Promise<number> {
  const db = getDb();
  const [result] = await db
    .select({ value: count() })
    .from(notifications)
    .where(
      and(eq(notifications.userId, userId), eq(notifications.isRead, false))
    );
  return result?.value ?? 0;
}

export async function markNotificationRead(
  userId: string,
  notificationId: string
): Promise<NotificationDto> {
  const db = getDb();
  const existing = await db.query.notifications.findFirst({
    where: and(
      eq(notifications.id, notificationId),
      eq(notifications.userId, userId)
    ),
  });

  if (!existing) {
    throw new NotificationServiceError("Notification not found", 404);
  }

  await db
    .update(notifications)
    .set({ isRead: true, readAt: new Date() })
    .where(eq(notifications.id, notificationId));

  const updated = await db.query.notifications.findFirst({
    where: eq(notifications.id, notificationId),
  });

  return mapNotification(updated!);
}

export async function markAllNotificationsRead(userId: string): Promise<void> {
  const db = getDb();
  await db
    .update(notifications)
    .set({ isRead: true, readAt: new Date() })
    .where(
      and(eq(notifications.userId, userId), eq(notifications.isRead, false))
    );
}

function mapNotification(
  row: typeof notifications.$inferSelect
): NotificationDto {
  return {
    id: row.id,
    title: row.title,
    body: row.body,
    type: row.type,
    channel: row.channel,
    isRead: row.isRead,
    metadata: (row.metadata as Record<string, unknown>) ?? {},
    createdAt: row.createdAt.toISOString(),
    readAt: row.readAt?.toISOString() ?? null,
  };
}
