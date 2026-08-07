import {
  boolean,
  datetime,
  decimal,
  json,
  mysqlTable,
  uniqueIndex,
  varchar,
} from "drizzle-orm/mysql-core";
import { attendanceStatusEnum, documentTypeEnum } from "./enums";
import { id, timestamps } from "./helpers";

export const employees = mysqlTable("employees", {
  id: id(),
  userId: varchar("user_id", { length: 36 }).notNull().unique(),
  branchId: varchar("branch_id", { length: 36 }).notNull(),
  employeeCode: varchar("employee_code", { length: 50 }).notNull().unique(),
  salary: decimal("salary", { precision: 12, scale: 2 }),
  skills: json("skills").$type<string[]>().default([]),
  availability: json("availability").$type<Record<string, unknown>>().default({}),
  currentLatitude: varchar("current_latitude", { length: 20 }),
  currentLongitude: varchar("current_longitude", { length: 20 }),
  locationUpdatedAt: datetime("location_updated_at", { mode: "date", fsp: 3 }),
  isAvailable: boolean("is_available").notNull().default(true),
  joinedAt: datetime("joined_at", { mode: "date", fsp: 3 })
    .notNull()
    .$defaultFn(() => new Date()),
  deletedAt: datetime("deleted_at", { mode: "date", fsp: 3 }),
  ...timestamps,
});

export const documents = mysqlTable("documents", {
  id: id(),
  employeeId: varchar("employee_id", { length: 36 }).notNull(),
  type: documentTypeEnum.notNull(),
  name: varchar("name", { length: 255 }).notNull(),
  url: varchar("url", { length: 500 }).notNull(),
  uploadedAt: datetime("uploaded_at", { mode: "date", fsp: 3 })
    .notNull()
    .$defaultFn(() => new Date()),
});

export const attendance = mysqlTable(
  "attendance",
  {
    id: id(),
    employeeId: varchar("employee_id", { length: 36 }).notNull(),
    date: datetime("date", { mode: "date" }).notNull(),
    checkIn: datetime("check_in", { mode: "date", fsp: 3 }),
    checkOut: datetime("check_out", { mode: "date", fsp: 3 }),
    status: attendanceStatusEnum.notNull().default("PRESENT"),
    notes: varchar("notes", { length: 500 }),
    latitude: varchar("latitude", { length: 20 }),
    longitude: varchar("longitude", { length: 20 }),
    createdAt: timestamps.createdAt,
  },
  (table) => [uniqueIndex("attendance_employee_date_idx").on(table.employeeId, table.date)]
);
