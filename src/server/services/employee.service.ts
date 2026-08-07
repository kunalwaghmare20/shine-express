import { createId } from "@paralleldrive/cuid2";
import {
  and,
  asc,
  count,
  desc,
  eq,
  isNull,
  like,
  or,
  sql,
} from "drizzle-orm";
import { getDb } from "@/lib/db";
import {
  attendance,
  branches,
  documents,
  employees,
  users,
} from "@/lib/db/schema";
import { UserRole } from "@/config/roles";
import type {
  CreateDocumentInput,
  CreateEmployeeInput,
  EmployeeListQuery,
  UpdateEmployeeInput,
} from "@/features/employees/validators/employee.schema";
import type {
  BranchOption,
  EmployeeAttendanceDto,
  EmployeeDetailDto,
  EmployeeDocumentDto,
  EmployeeListItem,
} from "@/server/dto/employee.dto";
import type { PaginatedResult } from "@/server/dto/pagination.dto";
import { toDecimalNumber } from "@/lib/utils/format";

export class EmployeeServiceError extends Error {
  constructor(
    message: string,
    public statusCode: number = 400
  ) {
    super(message);
  }
}

function generateEmployeeCode(): string {
  return `SE-${createId().slice(0, 8).toUpperCase()}`;
}

function mapDocument(row: typeof documents.$inferSelect): EmployeeDocumentDto {
  return {
    id: row.id,
    type: row.type,
    name: row.name,
    url: row.url,
    uploadedAt: row.uploadedAt.toISOString(),
  };
}

function mapAttendance(row: typeof attendance.$inferSelect): EmployeeAttendanceDto {
  return {
    id: row.id,
    date: row.date.toISOString().slice(0, 10),
    checkIn: row.checkIn?.toISOString() ?? null,
    checkOut: row.checkOut?.toISOString() ?? null,
    status: row.status,
    notes: row.notes,
  };
}

export async function listBranches(): Promise<BranchOption[]> {
  const db = getDb();
  const rows = await db
    .select({
      id: branches.id,
      name: branches.name,
      code: branches.code,
      city: branches.city,
    })
    .from(branches)
    .where(eq(branches.isActive, true))
    .orderBy(asc(branches.name));

  return rows;
}

export async function listEmployees(
  query: EmployeeListQuery,
  branchScope?: string
): Promise<PaginatedResult<EmployeeListItem>> {
  const db = getDb();
  const { page, limit, search, sort, order } = query;
  const branchId = branchScope ?? query.branchId;
  const offset = (page - 1) * limit;

  const searchFilter = search
    ? or(
        like(users.firstName, `%${search}%`),
        like(users.lastName, `%${search}%`),
        like(users.email, `%${search}%`),
        like(users.phone, `%${search}%`),
        like(employees.employeeCode, `%${search}%`)
      )
    : undefined;

  const whereClause = and(
    isNull(employees.deletedAt),
    branchId ? eq(employees.branchId, branchId) : undefined,
    searchFilter
  );

  const orderFn = order === "asc" ? asc : desc;
  const orderBy =
    sort === "firstName"
      ? orderFn(users.firstName)
      : sort === "employeeCode"
        ? orderFn(employees.employeeCode)
        : orderFn(employees.createdAt);

  const [rows, totalResult] = await Promise.all([
    db
      .select({
        id: employees.id,
        userId: employees.userId,
        employeeCode: employees.employeeCode,
        firstName: users.firstName,
        lastName: users.lastName,
        email: users.email,
        phone: users.phone,
        role: users.role,
        branchId: employees.branchId,
        branchName: branches.name,
        skills: employees.skills,
        salary: employees.salary,
        isAvailable: employees.isAvailable,
        isActive: users.isActive,
        joinedAt: employees.joinedAt,
        createdAt: employees.createdAt,
        jobsCount: sql<number>`(
          SELECT COUNT(*) FROM booking_assignments ba
          WHERE ba.employee_id = ${employees.id}
        )`.as("jobs_count"),
      })
      .from(employees)
      .innerJoin(users, eq(employees.userId, users.id))
      .innerJoin(branches, eq(employees.branchId, branches.id))
      .where(whereClause)
      .orderBy(orderBy)
      .limit(limit)
      .offset(offset),

    db
      .select({ value: count() })
      .from(employees)
      .innerJoin(users, eq(employees.userId, users.id))
      .where(whereClause),
  ]);

  const total = totalResult[0]?.value ?? 0;

  return {
    items: rows.map((row) => ({
      id: row.id,
      userId: row.userId,
      employeeCode: row.employeeCode,
      firstName: row.firstName,
      lastName: row.lastName,
      email: row.email,
      phone: row.phone,
      role: row.role,
      branchId: row.branchId,
      branchName: row.branchName,
      skills: (row.skills as string[]) ?? [],
      salary: row.salary ? toDecimalNumber(row.salary) : null,
      isAvailable: row.isAvailable,
      isActive: row.isActive,
      jobsCount: Number(row.jobsCount),
      joinedAt: row.joinedAt.toISOString(),
      createdAt: row.createdAt.toISOString(),
    })),
    page,
    limit,
    total,
    totalPages: Math.ceil(total / limit) || 1,
  };
}

export async function getEmployeeById(id: string): Promise<EmployeeDetailDto> {
  const db = getDb();

  const rows = await db
    .select({
      id: employees.id,
      userId: employees.userId,
      employeeCode: employees.employeeCode,
      firstName: users.firstName,
      lastName: users.lastName,
      email: users.email,
      phone: users.phone,
      role: users.role,
      avatarUrl: users.avatarUrl,
      branchId: employees.branchId,
      branchName: branches.name,
      skills: employees.skills,
      salary: employees.salary,
      availability: employees.availability,
      currentLatitude: employees.currentLatitude,
      currentLongitude: employees.currentLongitude,
      locationUpdatedAt: employees.locationUpdatedAt,
      isAvailable: employees.isAvailable,
      isActive: users.isActive,
      joinedAt: employees.joinedAt,
      createdAt: employees.createdAt,
      updatedAt: employees.updatedAt,
      jobsCount: sql<number>`(
        SELECT COUNT(*) FROM booking_assignments ba
        WHERE ba.employee_id = ${employees.id}
      )`.as("jobs_count"),
    })
    .from(employees)
    .innerJoin(users, eq(employees.userId, users.id))
    .innerJoin(branches, eq(employees.branchId, branches.id))
    .where(and(eq(employees.id, id), isNull(employees.deletedAt)))
    .limit(1);

  const row = rows[0];
  if (!row) {
    throw new EmployeeServiceError("Employee not found", 404);
  }

  const [docRows, attendanceRows] = await Promise.all([
    db
      .select()
      .from(documents)
      .where(eq(documents.employeeId, id))
      .orderBy(desc(documents.uploadedAt)),
    db
      .select()
      .from(attendance)
      .where(eq(attendance.employeeId, id))
      .orderBy(desc(attendance.date))
      .limit(10),
  ]);

  return {
    id: row.id,
    userId: row.userId,
    employeeCode: row.employeeCode,
    firstName: row.firstName,
    lastName: row.lastName,
    email: row.email,
    phone: row.phone,
    role: row.role,
    avatarUrl: row.avatarUrl,
    branchId: row.branchId,
    branchName: row.branchName,
    skills: (row.skills as string[]) ?? [],
    salary: row.salary ? toDecimalNumber(row.salary) : null,
    availability: (row.availability as Record<string, unknown>) ?? {},
    currentLatitude: row.currentLatitude,
    currentLongitude: row.currentLongitude,
    locationUpdatedAt: row.locationUpdatedAt?.toISOString() ?? null,
    isAvailable: row.isAvailable,
    isActive: row.isActive,
    jobsCount: Number(row.jobsCount),
    joinedAt: row.joinedAt.toISOString(),
    createdAt: row.createdAt.toISOString(),
    updatedAt: row.updatedAt.toISOString(),
    documents: docRows.map(mapDocument),
    recentAttendance: attendanceRows.map(mapAttendance),
  };
}

export async function createEmployee(
  input: CreateEmployeeInput
): Promise<EmployeeDetailDto> {
  const db = getDb();

  const branch = await db.query.branches.findFirst({
    where: eq(branches.id, input.branchId),
  });
  if (!branch) {
    throw new EmployeeServiceError("Branch not found", 404);
  }

  const existingEmail = await db.query.users.findFirst({
    where: eq(users.email, input.email),
  });
  if (existingEmail) {
    throw new EmployeeServiceError("Email already in use", 409);
  }

  const [userInsert] = await db
    .insert(users)
    .values({
      email: input.email,
      phone: input.phone,
      firstName: input.firstName,
      lastName: input.lastName,
      role: input.role as UserRole,
    })
    .$returningId();

  const [employeeInsert] = await db
    .insert(employees)
    .values({
      userId: userInsert.id,
      branchId: input.branchId,
      employeeCode: generateEmployeeCode(),
      salary: input.salary != null ? String(input.salary) : null,
      skills: input.skills,
      isAvailable: input.isAvailable,
    })
    .$returningId();

  return getEmployeeById(employeeInsert.id);
}

export async function updateEmployee(
  id: string,
  input: UpdateEmployeeInput
): Promise<EmployeeDetailDto> {
  const db = getDb();
  const employee = await getEmployeeById(id);

  if (input.branchId) {
    const branch = await db.query.branches.findFirst({
      where: eq(branches.id, input.branchId),
    });
    if (!branch) throw new EmployeeServiceError("Branch not found", 404);
  }

  if (input.email && input.email !== employee.email) {
    const existing = await db.query.users.findFirst({
      where: eq(users.email, input.email),
    });
    if (existing) throw new EmployeeServiceError("Email already in use", 409);
  }

  const userUpdates: Partial<typeof users.$inferInsert> = {};
  if (input.firstName) userUpdates.firstName = input.firstName;
  if (input.lastName) userUpdates.lastName = input.lastName;
  if (input.email) userUpdates.email = input.email;
  if (input.phone !== undefined) userUpdates.phone = input.phone;
  if (input.role) userUpdates.role = input.role as UserRole;
  if (input.isActive !== undefined) userUpdates.isActive = input.isActive;

  if (Object.keys(userUpdates).length > 0) {
    await db
      .update(users)
      .set(userUpdates)
      .where(eq(users.id, employee.userId));
  }

  const employeeUpdates: Partial<typeof employees.$inferInsert> = {};
  if (input.branchId) employeeUpdates.branchId = input.branchId;
  if (input.salary !== undefined) {
    employeeUpdates.salary = input.salary != null ? String(input.salary) : null;
  }
  if (input.skills) employeeUpdates.skills = input.skills;
  if (input.isAvailable !== undefined) {
    employeeUpdates.isAvailable = input.isAvailable;
  }

  if (Object.keys(employeeUpdates).length > 0) {
    await db.update(employees).set(employeeUpdates).where(eq(employees.id, id));
  }

  return getEmployeeById(id);
}

export async function deleteEmployee(id: string): Promise<void> {
  const db = getDb();
  const employee = await getEmployeeById(id);
  const now = new Date();

  await db
    .update(employees)
    .set({ deletedAt: now })
    .where(eq(employees.id, id));

  await db
    .update(users)
    .set({ deletedAt: now, isActive: false })
    .where(eq(users.id, employee.userId));
}

export async function addEmployeeDocument(
  employeeId: string,
  input: CreateDocumentInput
): Promise<EmployeeDocumentDto> {
  const db = getDb();
  await getEmployeeById(employeeId);

  const [inserted] = await db
    .insert(documents)
    .values({
      employeeId,
      type: input.type,
      name: input.name,
      url: input.url,
    })
    .$returningId();

  const doc = await db.query.documents.findFirst({
    where: eq(documents.id, inserted.id),
  });

  return mapDocument(doc!);
}

export async function deleteEmployeeDocument(
  employeeId: string,
  documentId: string
): Promise<void> {
  const db = getDb();
  await getEmployeeById(employeeId);

  const doc = await db.query.documents.findFirst({
    where: and(eq(documents.id, documentId), eq(documents.employeeId, employeeId)),
  });

  if (!doc) throw new EmployeeServiceError("Document not found", 404);

  await db.delete(documents).where(eq(documents.id, documentId));
}

export async function updateEmployeeLocation(
  employeeId: string,
  latitude: string,
  longitude: string
): Promise<void> {
  const db = getDb();
  await getEmployeeById(employeeId);

  await db
    .update(employees)
    .set({
      currentLatitude: latitude,
      currentLongitude: longitude,
      locationUpdatedAt: new Date(),
    })
    .where(eq(employees.id, employeeId));
}

export async function getEmployeeAttendance(
  employeeId: string,
  page = 1,
  limit = 20
): Promise<PaginatedResult<EmployeeAttendanceDto>> {
  const db = getDb();
  await getEmployeeById(employeeId);
  const offset = (page - 1) * limit;

  const whereClause = eq(attendance.employeeId, employeeId);

  const [rows, totalResult] = await Promise.all([
    db
      .select()
      .from(attendance)
      .where(whereClause)
      .orderBy(desc(attendance.date))
      .limit(limit)
      .offset(offset),
    db.select({ value: count() }).from(attendance).where(whereClause),
  ]);

  const total = totalResult[0]?.value ?? 0;

  return {
    items: rows.map(mapAttendance),
    page,
    limit,
    total,
    totalPages: Math.ceil(total / limit) || 1,
  };
}
