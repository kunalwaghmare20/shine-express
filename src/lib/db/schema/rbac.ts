import { boolean, mysqlTable, primaryKey, varchar } from "drizzle-orm/mysql-core";
import { userRoleEnum } from "./enums";
import { id, timestamps } from "./helpers";

export const roles = mysqlTable("roles", {
  id: id(),
  name: varchar("name", { length: 100 }).notNull(),
  slug: userRoleEnum.notNull().unique(),
  description: varchar("description", { length: 500 }),
  isSystem: boolean("is_system").notNull().default(false),
  ...timestamps,
});

export const permissions = mysqlTable("permissions", {
  id: id(),
  name: varchar("name", { length: 100 }).notNull(),
  slug: varchar("slug", { length: 100 }).notNull().unique(),
  description: varchar("description", { length: 500 }),
  module: varchar("module", { length: 50 }).notNull(),
  createdAt: timestamps.createdAt,
});

export const rolePermissions = mysqlTable(
  "role_permissions",
  {
    roleId: varchar("role_id", { length: 36 }).notNull(),
    permissionId: varchar("permission_id", { length: 36 }).notNull(),
  },
  (table) => [primaryKey({ columns: [table.roleId, table.permissionId] })]
);
