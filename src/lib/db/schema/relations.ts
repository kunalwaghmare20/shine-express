import { relations } from "drizzle-orm";
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

export const companiesRelations = relations(companies, ({ many }) => ({
  branches: many(branches),
}));

export const branchesRelations = relations(branches, ({ one, many }) => ({
  company: one(companies, {
    fields: [branches.companyId],
    references: [companies.id],
  }),
  employees: many(employees),
  bookings: many(bookings),
}));

export const rolesRelations = relations(roles, ({ many }) => ({
  permissions: many(rolePermissions),
}));

export const permissionsRelations = relations(permissions, ({ many }) => ({
  roles: many(rolePermissions),
}));

export const rolePermissionsRelations = relations(rolePermissions, ({ one }) => ({
  role: one(roles, {
    fields: [rolePermissions.roleId],
    references: [roles.id],
  }),
  permission: one(permissions, {
    fields: [rolePermissions.permissionId],
    references: [permissions.id],
  }),
}));

export const usersRelations = relations(users, ({ one, many }) => ({
  customer: one(customers, {
    fields: [users.id],
    references: [customers.userId],
  }),
  employee: one(employees, {
    fields: [users.id],
    references: [employees.userId],
  }),
  notifications: many(notifications),
  auditLogs: many(auditLogs),
  bookingAssignments: many(bookingAssignments),
  bookingStatusChanges: many(bookingStatusHistory),
}));

export const customersRelations = relations(customers, ({ one, many }) => ({
  user: one(users, {
    fields: [customers.userId],
    references: [users.id],
  }),
  addresses: many(addresses),
  bookings: many(bookings),
  invoices: many(invoices),
  payments: many(payments),
  reviews: many(reviews),
}));

export const addressesRelations = relations(addresses, ({ one, many }) => ({
  customer: one(customers, {
    fields: [addresses.customerId],
    references: [customers.id],
  }),
  bookings: many(bookings),
}));

export const employeesRelations = relations(employees, ({ one, many }) => ({
  user: one(users, {
    fields: [employees.userId],
    references: [users.id],
  }),
  branch: one(branches, {
    fields: [employees.branchId],
    references: [branches.id],
  }),
  documents: many(documents),
  attendance: many(attendance),
  assignments: many(bookingAssignments),
  photos: many(photos),
  reviews: many(reviews),
}));

export const documentsRelations = relations(documents, ({ one }) => ({
  employee: one(employees, {
    fields: [documents.employeeId],
    references: [employees.id],
  }),
}));

export const attendanceRelations = relations(attendance, ({ one }) => ({
  employee: one(employees, {
    fields: [attendance.employeeId],
    references: [employees.id],
  }),
}));

export const serviceCategoriesRelations = relations(
  serviceCategories,
  ({ many }) => ({
    services: many(services),
  })
);

export const servicesRelations = relations(services, ({ one, many }) => ({
  category: one(serviceCategories, {
    fields: [services.categoryId],
    references: [serviceCategories.id],
  }),
  items: many(serviceItems),
  bookings: many(bookings),
}));

export const serviceItemsRelations = relations(serviceItems, ({ one, many }) => ({
  service: one(services, {
    fields: [serviceItems.serviceId],
    references: [services.id],
  }),
  bookingItems: many(bookingItems),
}));

export const bookingsRelations = relations(bookings, ({ one, many }) => ({
  customer: one(customers, {
    fields: [bookings.customerId],
    references: [customers.id],
  }),
  branch: one(branches, {
    fields: [bookings.branchId],
    references: [branches.id],
  }),
  service: one(services, {
    fields: [bookings.serviceId],
    references: [services.id],
  }),
  address: one(addresses, {
    fields: [bookings.addressId],
    references: [addresses.id],
  }),
  items: many(bookingItems),
  assignments: many(bookingAssignments),
  statusHistory: many(bookingStatusHistory),
  photos: many(photos),
  invoice: one(invoices, {
    fields: [bookings.id],
    references: [invoices.bookingId],
  }),
  payments: many(payments),
  review: one(reviews, {
    fields: [bookings.id],
    references: [reviews.bookingId],
  }),
}));

export const bookingItemsRelations = relations(bookingItems, ({ one }) => ({
  booking: one(bookings, {
    fields: [bookingItems.bookingId],
    references: [bookings.id],
  }),
  serviceItem: one(serviceItems, {
    fields: [bookingItems.serviceItemId],
    references: [serviceItems.id],
  }),
}));

export const bookingAssignmentsRelations = relations(
  bookingAssignments,
  ({ one }) => ({
    booking: one(bookings, {
      fields: [bookingAssignments.bookingId],
      references: [bookings.id],
    }),
    employee: one(employees, {
      fields: [bookingAssignments.employeeId],
      references: [employees.id],
    }),
    assignedBy: one(users, {
      fields: [bookingAssignments.assignedById],
      references: [users.id],
    }),
  })
);

export const bookingStatusHistoryRelations = relations(
  bookingStatusHistory,
  ({ one }) => ({
    booking: one(bookings, {
      fields: [bookingStatusHistory.bookingId],
      references: [bookings.id],
    }),
    changedBy: one(users, {
      fields: [bookingStatusHistory.changedById],
      references: [users.id],
    }),
  })
);

export const photosRelations = relations(photos, ({ one }) => ({
  booking: one(bookings, {
    fields: [photos.bookingId],
    references: [bookings.id],
  }),
  employee: one(employees, {
    fields: [photos.employeeId],
    references: [employees.id],
  }),
}));

export const invoicesRelations = relations(invoices, ({ one, many }) => ({
  booking: one(bookings, {
    fields: [invoices.bookingId],
    references: [bookings.id],
  }),
  customer: one(customers, {
    fields: [invoices.customerId],
    references: [customers.id],
  }),
  payments: many(payments),
}));

export const paymentsRelations = relations(payments, ({ one }) => ({
  booking: one(bookings, {
    fields: [payments.bookingId],
    references: [bookings.id],
  }),
  invoice: one(invoices, {
    fields: [payments.invoiceId],
    references: [invoices.id],
  }),
  customer: one(customers, {
    fields: [payments.customerId],
    references: [customers.id],
  }),
}));

export const reviewsRelations = relations(reviews, ({ one }) => ({
  booking: one(bookings, {
    fields: [reviews.bookingId],
    references: [bookings.id],
  }),
  customer: one(customers, {
    fields: [reviews.customerId],
    references: [customers.id],
  }),
  employee: one(employees, {
    fields: [reviews.employeeId],
    references: [employees.id],
  }),
}));

export const notificationsRelations = relations(notifications, ({ one }) => ({
  user: one(users, {
    fields: [notifications.userId],
    references: [users.id],
  }),
}));

export const auditLogsRelations = relations(auditLogs, ({ one }) => ({
  user: one(users, {
    fields: [auditLogs.userId],
    references: [users.id],
  }),
}));
