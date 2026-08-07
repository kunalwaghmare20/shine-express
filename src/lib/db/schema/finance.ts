import {
  datetime,
  decimal,
  json,
  mysqlTable,
  varchar,
} from "drizzle-orm/mysql-core";
import { invoiceStatusEnum, paymentMethodEnum, paymentStatusEnum } from "./enums";
import { id, timestamps } from "./helpers";

export const invoices = mysqlTable("invoices", {
  id: id(),
  invoiceNumber: varchar("invoice_number", { length: 50 }).notNull().unique(),
  bookingId: varchar("booking_id", { length: 36 }).notNull().unique(),
  customerId: varchar("customer_id", { length: 36 }).notNull(),
  subtotal: decimal("subtotal", { precision: 12, scale: 2 }).notNull(),
  taxRate: decimal("tax_rate", { precision: 5, scale: 2 }).notNull(),
  taxAmount: decimal("tax_amount", { precision: 12, scale: 2 }).notNull(),
  cgst: decimal("cgst", { precision: 12, scale: 2 }).notNull().default("0"),
  sgst: decimal("sgst", { precision: 12, scale: 2 }).notNull().default("0"),
  igst: decimal("igst", { precision: 12, scale: 2 }).notNull().default("0"),
  discount: decimal("discount", { precision: 12, scale: 2 }).notNull().default("0"),
  totalAmount: decimal("total_amount", { precision: 12, scale: 2 }).notNull(),
  status: invoiceStatusEnum.notNull().default("DRAFT"),
  pdfUrl: varchar("pdf_url", { length: 500 }),
  issuedAt: datetime("issued_at", { mode: "date", fsp: 3 }),
  dueDate: datetime("due_date", { mode: "date" }),
  ...timestamps,
});

export const payments = mysqlTable("payments", {
  id: id(),
  bookingId: varchar("booking_id", { length: 36 }).notNull(),
  invoiceId: varchar("invoice_id", { length: 36 }),
  customerId: varchar("customer_id", { length: 36 }).notNull(),
  amount: decimal("amount", { precision: 12, scale: 2 }).notNull(),
  method: paymentMethodEnum.notNull(),
  status: paymentStatusEnum.notNull().default("PENDING"),
  transactionId: varchar("transaction_id", { length: 255 }),
  gatewayResponse: json("gateway_response").$type<Record<string, unknown>>(),
  paidAt: datetime("paid_at", { mode: "date", fsp: 3 }),
  ...timestamps,
});
