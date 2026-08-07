import {
  boolean,
  datetime,
  mysqlTable,
  varchar,
} from "drizzle-orm/mysql-core";
import { userRoleEnum } from "./enums";
import { id, timestamps } from "./helpers";

export const users = mysqlTable("users", {
  id: id(),
  clerkId: varchar("clerk_id", { length: 255 }).unique(),
  email: varchar("email", { length: 255 }).notNull().unique(),
  phone: varchar("phone", { length: 20 }),
  firstName: varchar("first_name", { length: 100 }).notNull(),
  lastName: varchar("last_name", { length: 100 }).notNull(),
  avatarUrl: varchar("avatar_url", { length: 500 }),
  role: userRoleEnum.notNull().default("CUSTOMER"),
  isActive: boolean("is_active").notNull().default(true),
  lastLoginAt: datetime("last_login_at", { mode: "date", fsp: 3 }),
  deletedAt: datetime("deleted_at", { mode: "date", fsp: 3 }),
  ...timestamps,
});

export const customers = mysqlTable("customers", {
  id: id(),
  userId: varchar("user_id", { length: 36 }).notNull().unique(),
  gstNumber: varchar("gst_number", { length: 20 }),
  notes: varchar("notes", { length: 2000 }),
  deletedAt: datetime("deleted_at", { mode: "date", fsp: 3 }),
  ...timestamps,
});

export const addresses = mysqlTable("addresses", {
  id: id(),
  customerId: varchar("customer_id", { length: 36 }).notNull(),
  label: varchar("label", { length: 50 }).notNull().default("Home"),
  line1: varchar("line1", { length: 255 }).notNull(),
  line2: varchar("line2", { length: 255 }),
  city: varchar("city", { length: 100 }).notNull(),
  state: varchar("state", { length: 100 }).notNull(),
  pincode: varchar("pincode", { length: 10 }).notNull(),
  country: varchar("country", { length: 100 }).notNull().default("India"),
  latitude: varchar("latitude", { length: 20 }),
  longitude: varchar("longitude", { length: 20 }),
  isDefault: boolean("is_default").notNull().default(false),
  ...timestamps,
});
