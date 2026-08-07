export * from "./enums";
export * from "./organization";
export * from "./rbac";
export * from "./users";
export * from "./employees";
export * from "./services";
export * from "./bookings";
export * from "./finance";
export * from "./misc";
export * from "./relations";

import { companies, branches } from "./organization";
import { roles, permissions, rolePermissions } from "./rbac";
import { users, customers, addresses } from "./users";
import { employees, documents, attendance } from "./employees";
import { serviceCategories, services, serviceItems } from "./services";
import {
  bookings,
  bookingItems,
  bookingAssignments,
  bookingStatusHistory,
  photos,
} from "./bookings";
import { invoices, payments } from "./finance";
import { reviews, notifications, auditLogs } from "./misc";

/** All tables — used by Drizzle client and migrations */
export const schema = {
  companies,
  branches,
  roles,
  permissions,
  rolePermissions,
  users,
  customers,
  addresses,
  employees,
  documents,
  attendance,
  serviceCategories,
  services,
  serviceItems,
  bookings,
  bookingItems,
  bookingAssignments,
  bookingStatusHistory,
  photos,
  invoices,
  payments,
  reviews,
  notifications,
  auditLogs,
};
