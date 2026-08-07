import {
  boolean,
  decimal,
  int,
  json,
  mysqlTable,
  varchar,
} from "drizzle-orm/mysql-core";
import { id, timestamps } from "./helpers";

export const serviceCategories = mysqlTable("service_categories", {
  id: id(),
  name: varchar("name", { length: 255 }).notNull(),
  slug: varchar("slug", { length: 255 }).notNull().unique(),
  description: varchar("description", { length: 1000 }),
  icon: varchar("icon", { length: 50 }),
  sortOrder: int("sort_order").notNull().default(0),
  isActive: boolean("is_active").notNull().default(true),
  ...timestamps,
});

export const services = mysqlTable("services", {
  id: id(),
  categoryId: varchar("category_id", { length: 36 }).notNull(),
  name: varchar("name", { length: 255 }).notNull(),
  slug: varchar("slug", { length: 255 }).notNull().unique(),
  description: varchar("description", { length: 2000 }),
  basePrice: decimal("base_price", { precision: 10, scale: 2 }).notNull(),
  duration: int("duration").notNull(),
  images: json("images").$type<string[]>().default([]),
  isActive: boolean("is_active").notNull().default(true),
  sortOrder: int("sort_order").notNull().default(0),
  ...timestamps,
});

export const serviceItems = mysqlTable("service_items", {
  id: id(),
  serviceId: varchar("service_id", { length: 36 }).notNull(),
  name: varchar("name", { length: 255 }).notNull(),
  description: varchar("description", { length: 1000 }),
  price: decimal("price", { precision: 10, scale: 2 }).notNull(),
  duration: int("duration"),
  isActive: boolean("is_active").notNull().default(true),
  sortOrder: int("sort_order").notNull().default(0),
  ...timestamps,
});
