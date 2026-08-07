import {
  boolean,
  datetime,
  int,
  json,
  mysqlTable,
  varchar,
} from "drizzle-orm/mysql-core";
import {
  auditActionEnum,
  notificationChannelEnum,
  notificationTypeEnum,
} from "./enums";
import { id, timestamps } from "./helpers";

export const reviews = mysqlTable("reviews", {
  id: id(),
  bookingId: varchar("booking_id", { length: 36 }).notNull().unique(),
  customerId: varchar("customer_id", { length: 36 }).notNull(),
  employeeId: varchar("employee_id", { length: 36 }),
  rating: int("rating").notNull(),
  comment: varchar("comment", { length: 2000 }),
  createdAt: timestamps.createdAt,
});

export const notifications = mysqlTable("notifications", {
  id: id(),
  userId: varchar("user_id", { length: 36 }).notNull(),
  title: varchar("title", { length: 255 }).notNull(),
  body: varchar("body", { length: 2000 }).notNull(),
  type: notificationTypeEnum.notNull().default("GENERAL"),
  channel: notificationChannelEnum.notNull().default("IN_APP"),
  isRead: boolean("is_read").notNull().default(false),
  metadata: json("metadata").$type<Record<string, unknown>>().default({}),
  sentAt: datetime("sent_at", { mode: "date", fsp: 3 }),
  readAt: datetime("read_at", { mode: "date", fsp: 3 }),
  createdAt: timestamps.createdAt,
});

export const auditLogs = mysqlTable("audit_logs", {
  id: id(),
  userId: varchar("user_id", { length: 36 }),
  action: auditActionEnum.notNull(),
  entity: varchar("entity", { length: 100 }).notNull(),
  entityId: varchar("entity_id", { length: 36 }).notNull(),
  oldValues: json("old_values").$type<Record<string, unknown>>(),
  newValues: json("new_values").$type<Record<string, unknown>>(),
  ipAddress: varchar("ip_address", { length: 45 }),
  userAgent: varchar("user_agent", { length: 500 }),
  createdAt: timestamps.createdAt,
});
