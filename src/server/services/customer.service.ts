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
  addresses,
  bookings,
  customers,
  payments,
  services,
  users,
} from "@/lib/db/schema";
import { UserRole } from "@/config/roles";
import type {
  CreateAddressInput,
  CreateCustomerInput,
  CustomerListQuery,
  UpdateAddressInput,
  UpdateCustomerInput,
} from "@/features/customers/validators/customer.schema";
import type {
  CustomerAddressDto,
  CustomerBookingHistoryItem,
  CustomerDetailDto,
  CustomerListItem,
  CustomerPaymentItem,
} from "@/server/dto/customer.dto";
import type { PaginatedResult } from "@/server/dto/pagination.dto";
import { toDecimalNumber } from "@/lib/utils/format";

export class CustomerServiceError extends Error {
  constructor(
    message: string,
    public statusCode: number = 400
  ) {
    super(message);
  }
}

function mapAddress(row: typeof addresses.$inferSelect): CustomerAddressDto {
  return {
    id: row.id,
    label: row.label,
    line1: row.line1,
    line2: row.line2,
    city: row.city,
    state: row.state,
    pincode: row.pincode,
    country: row.country,
    isDefault: row.isDefault,
  };
}

export async function listCustomers(
  query: CustomerListQuery
): Promise<PaginatedResult<CustomerListItem>> {
  const db = getDb();
  const { page, limit, search, sort, order } = query;
  const offset = (page - 1) * limit;

  const searchFilter = search
    ? or(
        like(users.firstName, `%${search}%`),
        like(users.lastName, `%${search}%`),
        like(users.email, `%${search}%`),
        like(users.phone, `%${search}%`),
        like(customers.gstNumber, `%${search}%`)
      )
    : undefined;

  const whereClause = and(
    isNull(customers.deletedAt),
    eq(users.role, UserRole.CUSTOMER),
    searchFilter
  );

  const orderFn = order === "asc" ? asc : desc;
  const orderBy =
    sort === "firstName"
      ? orderFn(users.firstName)
      : sort === "email"
        ? orderFn(users.email)
        : orderFn(customers.createdAt);

  const [rows, totalResult] = await Promise.all([
    db
      .select({
        id: customers.id,
        userId: customers.userId,
        firstName: users.firstName,
        lastName: users.lastName,
        email: users.email,
        phone: users.phone,
        gstNumber: customers.gstNumber,
        isActive: users.isActive,
        createdAt: customers.createdAt,
        bookingsCount: sql<number>`(
          SELECT COUNT(*) FROM bookings b WHERE b.customer_id = ${customers.id}
        )`.as("bookings_count"),
        totalSpent: sql<string>`(
          SELECT COALESCE(SUM(p.amount), 0) FROM payments p
          WHERE p.customer_id = ${customers.id} AND p.status = 'COMPLETED'
        )`.as("total_spent"),
      })
      .from(customers)
      .innerJoin(users, eq(customers.userId, users.id))
      .where(whereClause)
      .orderBy(orderBy)
      .limit(limit)
      .offset(offset),

    db
      .select({ value: count() })
      .from(customers)
      .innerJoin(users, eq(customers.userId, users.id))
      .where(whereClause),
  ]);

  const total = totalResult[0]?.value ?? 0;

  return {
    items: rows.map((row) => ({
      id: row.id,
      userId: row.userId,
      firstName: row.firstName,
      lastName: row.lastName,
      email: row.email,
      phone: row.phone,
      gstNumber: row.gstNumber,
      isActive: row.isActive,
      bookingsCount: Number(row.bookingsCount),
      totalSpent: toDecimalNumber(row.totalSpent),
      createdAt: row.createdAt.toISOString(),
    })),
    page,
    limit,
    total,
    totalPages: Math.ceil(total / limit) || 1,
  };
}

export async function getCustomerById(id: string): Promise<CustomerDetailDto> {
  const db = getDb();

  const row = await db
    .select({
      id: customers.id,
      userId: customers.userId,
      firstName: users.firstName,
      lastName: users.lastName,
      email: users.email,
      phone: users.phone,
      gstNumber: customers.gstNumber,
      notes: customers.notes,
      isActive: users.isActive,
      createdAt: customers.createdAt,
      updatedAt: customers.updatedAt,
      bookingsCount: sql<number>`(
        SELECT COUNT(*) FROM bookings b WHERE b.customer_id = ${customers.id}
      )`.as("bookings_count"),
      totalSpent: sql<string>`(
        SELECT COALESCE(SUM(p.amount), 0) FROM payments p
        WHERE p.customer_id = ${customers.id} AND p.status = 'COMPLETED'
      )`.as("total_spent"),
    })
    .from(customers)
    .innerJoin(users, eq(customers.userId, users.id))
    .where(and(eq(customers.id, id), isNull(customers.deletedAt)))
    .limit(1);

  const customer = row[0];
  if (!customer) {
    throw new CustomerServiceError("Customer not found", 404);
  }

  const addressRows = await db
    .select()
    .from(addresses)
    .where(eq(addresses.customerId, id))
    .orderBy(desc(addresses.isDefault), desc(addresses.createdAt));

  return {
    id: customer.id,
    userId: customer.userId,
    firstName: customer.firstName,
    lastName: customer.lastName,
    email: customer.email,
    phone: customer.phone,
    gstNumber: customer.gstNumber,
    notes: customer.notes,
    isActive: customer.isActive,
    bookingsCount: Number(customer.bookingsCount),
    totalSpent: toDecimalNumber(customer.totalSpent),
    createdAt: customer.createdAt.toISOString(),
    updatedAt: customer.updatedAt.toISOString(),
    addresses: addressRows.map(mapAddress),
  };
}

export async function createCustomer(
  input: CreateCustomerInput
): Promise<CustomerDetailDto> {
  const db = getDb();

  const existingEmail = await db.query.users.findFirst({
    where: eq(users.email, input.email),
  });

  if (existingEmail) {
    throw new CustomerServiceError("Email already in use", 409);
  }

  const [userInsert] = await db
    .insert(users)
    .values({
      email: input.email,
      phone: input.phone,
      firstName: input.firstName,
      lastName: input.lastName,
      role: UserRole.CUSTOMER,
    })
    .$returningId();

  const [customerInsert] = await db
    .insert(customers)
    .values({
      userId: userInsert.id,
      gstNumber: input.gstNumber ?? null,
      notes: input.notes ?? null,
    })
    .$returningId();

  return getCustomerById(customerInsert.id);
}

export async function updateCustomer(
  id: string,
  input: UpdateCustomerInput
): Promise<CustomerDetailDto> {
  const db = getDb();
  const customer = await getCustomerById(id);

  if (input.email && input.email !== customer.email) {
    const existingEmail = await db.query.users.findFirst({
      where: eq(users.email, input.email),
    });
    if (existingEmail) {
      throw new CustomerServiceError("Email already in use", 409);
    }
  }

  if (
    input.firstName ||
    input.lastName ||
    input.email ||
    input.phone !== undefined
  ) {
    await db
      .update(users)
      .set({
        ...(input.firstName && { firstName: input.firstName }),
        ...(input.lastName && { lastName: input.lastName }),
        ...(input.email && { email: input.email }),
        ...(input.phone !== undefined && { phone: input.phone }),
      })
      .where(eq(users.id, customer.userId));
  }

  if (input.gstNumber !== undefined || input.notes !== undefined) {
    await db
      .update(customers)
      .set({
        ...(input.gstNumber !== undefined && { gstNumber: input.gstNumber }),
        ...(input.notes !== undefined && { notes: input.notes }),
      })
      .where(eq(customers.id, id));
  }

  return getCustomerById(id);
}

export async function deleteCustomer(id: string): Promise<void> {
  const db = getDb();
  const customer = await getCustomerById(id);
  const now = new Date();

  await db
    .update(customers)
    .set({ deletedAt: now })
    .where(eq(customers.id, id));

  await db
    .update(users)
    .set({ deletedAt: now, isActive: false })
    .where(eq(users.id, customer.userId));
}

export async function addCustomerAddress(
  customerId: string,
  input: CreateAddressInput
): Promise<CustomerAddressDto> {
  const db = getDb();
  await getCustomerById(customerId);

  if (input.isDefault) {
    await db
      .update(addresses)
      .set({ isDefault: false })
      .where(eq(addresses.customerId, customerId));
  }

  const [inserted] = await db
    .insert(addresses)
    .values({
      customerId,
      label: input.label,
      line1: input.line1,
      line2: input.line2 ?? null,
      city: input.city,
      state: input.state,
      pincode: input.pincode,
      country: input.country,
      isDefault: input.isDefault,
    })
    .$returningId();

  const address = await db.query.addresses.findFirst({
    where: eq(addresses.id, inserted.id),
  });

  return mapAddress(address!);
}

export async function updateCustomerAddress(
  customerId: string,
  addressId: string,
  input: UpdateAddressInput
): Promise<CustomerAddressDto> {
  const db = getDb();
  await getCustomerById(customerId);

  const existing = await db.query.addresses.findFirst({
    where: and(eq(addresses.id, addressId), eq(addresses.customerId, customerId)),
  });

  if (!existing) {
    throw new CustomerServiceError("Address not found", 404);
  }

  if (input.isDefault) {
    await db
      .update(addresses)
      .set({ isDefault: false })
      .where(eq(addresses.customerId, customerId));
  }

  await db
    .update(addresses)
    .set({
      ...(input.label !== undefined && { label: input.label }),
      ...(input.line1 !== undefined && { line1: input.line1 }),
      ...(input.line2 !== undefined && { line2: input.line2 }),
      ...(input.city !== undefined && { city: input.city }),
      ...(input.state !== undefined && { state: input.state }),
      ...(input.pincode !== undefined && { pincode: input.pincode }),
      ...(input.country !== undefined && { country: input.country }),
      ...(input.isDefault !== undefined && { isDefault: input.isDefault }),
    })
    .where(eq(addresses.id, addressId));

  const updated = await db.query.addresses.findFirst({
    where: eq(addresses.id, addressId),
  });

  return mapAddress(updated!);
}

export async function deleteCustomerAddress(
  customerId: string,
  addressId: string
): Promise<void> {
  const db = getDb();
  await getCustomerById(customerId);

  const existing = await db.query.addresses.findFirst({
    where: and(eq(addresses.id, addressId), eq(addresses.customerId, customerId)),
  });

  if (!existing) {
    throw new CustomerServiceError("Address not found", 404);
  }

  await db.delete(addresses).where(eq(addresses.id, addressId));
}

export async function getCustomerBookings(
  customerId: string,
  page = 1,
  limit = 10
): Promise<PaginatedResult<CustomerBookingHistoryItem>> {
  const db = getDb();
  await getCustomerById(customerId);
  const offset = (page - 1) * limit;

  const whereClause = eq(bookings.customerId, customerId);

  const [rows, totalResult] = await Promise.all([
    db
      .select({
        id: bookings.id,
        bookingNumber: bookings.bookingNumber,
        serviceName: services.name,
        status: bookings.status,
        scheduledDate: bookings.scheduledDate,
        totalAmount: bookings.totalAmount,
        createdAt: bookings.createdAt,
      })
      .from(bookings)
      .innerJoin(services, eq(bookings.serviceId, services.id))
      .where(whereClause)
      .orderBy(desc(bookings.createdAt))
      .limit(limit)
      .offset(offset),

    db.select({ value: count() }).from(bookings).where(whereClause),
  ]);

  const total = totalResult[0]?.value ?? 0;

  return {
    items: rows.map((row) => ({
      id: row.id,
      bookingNumber: row.bookingNumber,
      serviceName: row.serviceName,
      status: row.status,
      scheduledDate: row.scheduledDate.toISOString().slice(0, 10),
      totalAmount: toDecimalNumber(row.totalAmount),
      createdAt: row.createdAt.toISOString(),
    })),
    page,
    limit,
    total,
    totalPages: Math.ceil(total / limit) || 1,
  };
}

export async function getCustomerPayments(
  customerId: string,
  page = 1,
  limit = 10
): Promise<PaginatedResult<CustomerPaymentItem>> {
  const db = getDb();
  await getCustomerById(customerId);
  const offset = (page - 1) * limit;

  const whereClause = eq(payments.customerId, customerId);

  const [rows, totalResult] = await Promise.all([
    db
      .select({
        id: payments.id,
        amount: payments.amount,
        method: payments.method,
        status: payments.status,
        paidAt: payments.paidAt,
        bookingNumber: bookings.bookingNumber,
      })
      .from(payments)
      .innerJoin(bookings, eq(payments.bookingId, bookings.id))
      .where(whereClause)
      .orderBy(desc(payments.createdAt))
      .limit(limit)
      .offset(offset),

    db.select({ value: count() }).from(payments).where(whereClause),
  ]);

  const total = totalResult[0]?.value ?? 0;

  return {
    items: rows.map((row) => ({
      id: row.id,
      amount: toDecimalNumber(row.amount),
      method: row.method,
      status: row.status,
      paidAt: row.paidAt?.toISOString() ?? null,
      bookingNumber: row.bookingNumber,
    })),
    page,
    limit,
    total,
    totalPages: Math.ceil(total / limit) || 1,
  };
}
