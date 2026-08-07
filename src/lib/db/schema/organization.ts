import {
  boolean,
  double,
  json,
  mysqlTable,
  varchar,
} from "drizzle-orm/mysql-core";
import { id, timestamps } from "./helpers";

export const companies = mysqlTable("companies", {
  id: id(),
  name: varchar("name", { length: 255 }).notNull(),
  slug: varchar("slug", { length: 255 }).notNull().unique(),
  logo: varchar("logo", { length: 500 }),
  gstNumber: varchar("gst_number", { length: 20 }),
  panNumber: varchar("pan_number", { length: 20 }),
  email: varchar("email", { length: 255 }),
  phone: varchar("phone", { length: 20 }),
  website: varchar("website", { length: 255 }),
  address: varchar("address", { length: 500 }),
  city: varchar("city", { length: 100 }),
  state: varchar("state", { length: 100 }),
  country: varchar("country", { length: 100 }).notNull().default("India"),
  pincode: varchar("pincode", { length: 10 }),
  settings: json("settings").$type<Record<string, unknown>>().default({}),
  isActive: boolean("is_active").notNull().default(true),
  ...timestamps,
});

export const branches = mysqlTable("branches", {
  id: id(),
  companyId: varchar("company_id", { length: 36 }).notNull(),
  name: varchar("name", { length: 255 }).notNull(),
  code: varchar("code", { length: 50 }).notNull().unique(),
  email: varchar("email", { length: 255 }),
  phone: varchar("phone", { length: 20 }),
  address: varchar("address", { length: 500 }).notNull(),
  city: varchar("city", { length: 100 }).notNull(),
  state: varchar("state", { length: 100 }).notNull(),
  pincode: varchar("pincode", { length: 10 }).notNull(),
  latitude: double("latitude"),
  longitude: double("longitude"),
  isActive: boolean("is_active").notNull().default(true),
  ...timestamps,
});
