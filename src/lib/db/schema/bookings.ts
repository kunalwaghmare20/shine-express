import {
  boolean,
  datetime,
  decimal,
  int,
  mysqlTable,
  uniqueIndex,
  varchar,
} from "drizzle-orm/mysql-core";
import { bookingStatusEnum, photoTypeEnum } from "./enums";
import { id, timestamps } from "./helpers";

export const bookings = mysqlTable("bookings", {
  id: id(),
  bookingNumber: varchar("booking_number", { length: 50 }).notNull().unique(),
  customerId: varchar("customer_id", { length: 36 }).notNull(),
  branchId: varchar("branch_id", { length: 36 }).notNull(),
  serviceId: varchar("service_id", { length: 36 }).notNull(),
  addressId: varchar("address_id", { length: 36 }).notNull(),
  status: bookingStatusEnum.notNull().default("PENDING"),
  scheduledDate: datetime("scheduled_date", { mode: "date" }).notNull(),
  scheduledTime: varchar("scheduled_time", { length: 10 }).notNull(),
  estimatedDuration: int("estimated_duration").notNull(),
  customerNotes: varchar("customer_notes", { length: 2000 }),
  internalNotes: varchar("internal_notes", { length: 2000 }),
  subtotal: decimal("subtotal", { precision: 12, scale: 2 }).notNull().default("0"),
  taxRate: decimal("tax_rate", { precision: 5, scale: 2 }).notNull().default("18"),
  taxAmount: decimal("tax_amount", { precision: 12, scale: 2 }).notNull().default("0"),
  discount: decimal("discount", { precision: 12, scale: 2 }).notNull().default("0"),
  totalAmount: decimal("total_amount", { precision: 12, scale: 2 }).notNull().default("0"),
  assignedAt: datetime("assigned_at", { mode: "date", fsp: 3 }),
  acceptedAt: datetime("accepted_at", { mode: "date", fsp: 3 }),
  startedAt: datetime("started_at", { mode: "date", fsp: 3 }),
  completedAt: datetime("completed_at", { mode: "date", fsp: 3 }),
  cancelledAt: datetime("cancelled_at", { mode: "date", fsp: 3 }),
  cancellationReason: varchar("cancellation_reason", { length: 500 }),
  ...timestamps,
});

export const bookingItems = mysqlTable("booking_items", {
  id: id(),
  bookingId: varchar("booking_id", { length: 36 }).notNull(),
  serviceItemId: varchar("service_item_id", { length: 36 }),
  name: varchar("name", { length: 255 }).notNull(),
  description: varchar("description", { length: 1000 }),
  price: decimal("price", { precision: 10, scale: 2 }).notNull(),
  quantity: int("quantity").notNull().default(1),
});

export const bookingAssignments = mysqlTable(
  "booking_assignments",
  {
    id: id(),
    bookingId: varchar("booking_id", { length: 36 }).notNull(),
    employeeId: varchar("employee_id", { length: 36 }).notNull(),
    assignedById: varchar("assigned_by_id", { length: 36 }),
    isPrimary: boolean("is_primary").notNull().default(true),
    acceptedAt: datetime("accepted_at", { mode: "date", fsp: 3 }),
    rejectedAt: datetime("rejected_at", { mode: "date", fsp: 3 }),
    rejectionReason: varchar("rejection_reason", { length: 500 }),
    createdAt: timestamps.createdAt,
  },
  (table) => [
    uniqueIndex("booking_assignments_booking_employee_idx").on(
      table.bookingId,
      table.employeeId
    ),
  ]
);

export const bookingStatusHistory = mysqlTable("booking_status_history", {
  id: id(),
  bookingId: varchar("booking_id", { length: 36 }).notNull(),
  fromStatus: bookingStatusEnum,
  toStatus: bookingStatusEnum.notNull(),
  changedById: varchar("changed_by_id", { length: 36 }),
  notes: varchar("notes", { length: 500 }),
  createdAt: timestamps.createdAt,
});

export const photos = mysqlTable("photos", {
  id: id(),
  bookingId: varchar("booking_id", { length: 36 }).notNull(),
  employeeId: varchar("employee_id", { length: 36 }),
  url: varchar("url", { length: 500 }).notNull(),
  type: photoTypeEnum.notNull(),
  caption: varchar("caption", { length: 500 }),
  createdAt: timestamps.createdAt,
});
