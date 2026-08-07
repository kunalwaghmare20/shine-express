/**
 * Database types inferred from Drizzle schema.
 */
import type { InferSelectModel } from "drizzle-orm";
import {
  users,
  customers,
  employees,
  bookings,
  services,
  invoices,
  payments,
  companies,
  branches,
  roles,
  permissions,
  addresses,
  serviceCategories,
  serviceItems,
  notifications,
  reviews,
} from "@/lib/db/schema";

export type UserRecord = InferSelectModel<typeof users>;
export type CustomerRecord = InferSelectModel<typeof customers>;
export type EmployeeRecord = InferSelectModel<typeof employees>;
export type BookingRecord = InferSelectModel<typeof bookings>;
export type ServiceRecord = InferSelectModel<typeof services>;
export type InvoiceRecord = InferSelectModel<typeof invoices>;
export type PaymentRecord = InferSelectModel<typeof payments>;
export type CompanyRecord = InferSelectModel<typeof companies>;
export type BranchRecord = InferSelectModel<typeof branches>;
export type RoleRecord = InferSelectModel<typeof roles>;
export type PermissionRecord = InferSelectModel<typeof permissions>;
export type AddressRecord = InferSelectModel<typeof addresses>;
export type ServiceCategoryRecord = InferSelectModel<typeof serviceCategories>;
export type ServiceItemRecord = InferSelectModel<typeof serviceItems>;
export type NotificationRecord = InferSelectModel<typeof notifications>;
export type ReviewRecord = InferSelectModel<typeof reviews>;

export type UserRoleType = UserRecord["role"];
export type BookingStatusType = BookingRecord["status"];
export type PaymentMethodType = PaymentRecord["method"];
export type PaymentStatusType = PaymentRecord["status"];
