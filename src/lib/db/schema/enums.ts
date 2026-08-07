import { mysqlEnum } from "drizzle-orm/mysql-core";

export const userRoleEnum = mysqlEnum("user_role", [
  "SUPER_ADMIN",
  "BRANCH_MANAGER",
  "SERVICE_STAFF",
  "CUSTOMER",
]);

export const bookingStatusEnum = mysqlEnum("booking_status", [
  "PENDING",
  "CONFIRMED",
  "ASSIGNED",
  "ACCEPTED",
  "ON_THE_WAY",
  "STARTED",
  "COMPLETED",
  "CANCELLED",
  "REJECTED",
]);

export const paymentMethodEnum = mysqlEnum("payment_method", [
  "CASH",
  "UPI",
  "CARD",
  "RAZORPAY",
  "STRIPE",
]);

export const paymentStatusEnum = mysqlEnum("payment_status", [
  "PENDING",
  "COMPLETED",
  "FAILED",
  "REFUNDED",
]);

export const invoiceStatusEnum = mysqlEnum("invoice_status", [
  "DRAFT",
  "ISSUED",
  "PAID",
  "OVERDUE",
  "CANCELLED",
]);

export const photoTypeEnum = mysqlEnum("photo_type", ["BEFORE", "AFTER"]);

export const documentTypeEnum = mysqlEnum("document_type", [
  "ID_PROOF",
  "ADDRESS_PROOF",
  "CONTRACT",
  "CERTIFICATE",
  "OTHER",
]);

export const notificationTypeEnum = mysqlEnum("notification_type", [
  "BOOKING_CREATED",
  "BOOKING_CONFIRMED",
  "BOOKING_ASSIGNED",
  "BOOKING_STARTED",
  "BOOKING_COMPLETED",
  "BOOKING_CANCELLED",
  "PAYMENT_RECEIVED",
  "INVOICE_GENERATED",
  "REVIEW_REQUEST",
  "GENERAL",
]);

export const notificationChannelEnum = mysqlEnum("notification_channel", [
  "IN_APP",
  "EMAIL",
  "SMS",
  "WHATSAPP",
]);

export const attendanceStatusEnum = mysqlEnum("attendance_status", [
  "PRESENT",
  "ABSENT",
  "HALF_DAY",
  "LEAVE",
]);

export const auditActionEnum = mysqlEnum("audit_action", [
  "CREATE",
  "UPDATE",
  "DELETE",
  "LOGIN",
  "LOGOUT",
  "STATUS_CHANGE",
  "ASSIGN",
  "PAYMENT",
]);
