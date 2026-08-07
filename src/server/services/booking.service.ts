import { createId } from "@paralleldrive/cuid2";
import {
  and,
  asc,
  count,
  desc,
  eq,
  gte,
  inArray,
  like,
  lte,
  or,
  sql,
} from "drizzle-orm";
import { getDb } from "@/lib/db";
import {
  addresses,
  bookingAssignments,
  bookingItems,
  bookings,
  bookingStatusHistory,
  branches,
  customers,
  employees,
  serviceCategories,
  serviceItems,
  services,
  users,
  payments,
} from "@/lib/db/schema";
import {
  BookingStatus,
  canTransitionBookingStatus,
} from "@/types/booking";
import type {
  AssignStaffInput,
  BookingListQuery,
  CreateBookingInput,
  UpdateBookingStatusInput,
} from "@/features/bookings/validators/booking.schema";
import type {
  BookingAssignmentDto,
  BookingCatalogService,
  BookingDetailDto,
  BookingItemDto,
  BookingListItem,
  BookingStatusHistoryDto,
} from "@/server/dto/booking.dto";
import type { PaginatedResult } from "@/server/dto/pagination.dto";
import { toDecimalNumber } from "@/lib/utils/format";
import type { BookingAccessContext } from "@/lib/auth/booking-access";

const TAX_RATE = 18;

export class BookingServiceError extends Error {
  constructor(
    message: string,
    public statusCode: number = 400
  ) {
    super(message);
  }
}

function generateBookingNumber(): string {
  const date = new Date();
  const ymd = date.toISOString().slice(0, 10).replace(/-/g, "");
  return `SE-${ymd}-${createId().slice(0, 6).toUpperCase()}`;
}

function assertTransition(from: BookingStatus, to: BookingStatus) {
  if (!canTransitionBookingStatus(from, to)) {
    throw new BookingServiceError(
      `Invalid status transition: ${from} → ${to}`,
      400
    );
  }
}

async function recordStatusChange(
  bookingId: string,
  fromStatus: BookingStatus | null,
  toStatus: BookingStatus,
  changedById: string | null,
  notes?: string | null
) {
  const db = getDb();
  await db.insert(bookingStatusHistory).values({
    bookingId,
    fromStatus: fromStatus ?? undefined,
    toStatus,
    changedById,
    notes: notes ?? null,
  });
}

export async function listBookingCatalog(): Promise<BookingCatalogService[]> {
  const db = getDb();

  const serviceRows = await db
    .select({
      id: services.id,
      name: services.name,
      basePrice: services.basePrice,
      duration: services.duration,
      categoryName: serviceCategories.name,
    })
    .from(services)
    .innerJoin(
      serviceCategories,
      eq(services.categoryId, serviceCategories.id)
    )
    .where(eq(services.isActive, true))
    .orderBy(asc(services.sortOrder));

  const allItems = await db
    .select()
    .from(serviceItems)
    .where(eq(serviceItems.isActive, true))
    .orderBy(asc(serviceItems.sortOrder));

  const itemsByService = new Map<string, typeof allItems>();
  for (const item of allItems) {
    const list = itemsByService.get(item.serviceId) ?? [];
    list.push(item);
    itemsByService.set(item.serviceId, list);
  }

  return serviceRows.map((s) => ({
    id: s.id,
    name: s.name,
    basePrice: toDecimalNumber(s.basePrice),
    duration: s.duration,
    categoryName: s.categoryName,
    items: (itemsByService.get(s.id) ?? []).map((i) => ({
      id: i.id,
      name: i.name,
      price: toDecimalNumber(i.price),
      duration: i.duration,
    })),
  }));
}

export async function listBookings(
  query: BookingListQuery,
  ctx?: BookingAccessContext
): Promise<PaginatedResult<BookingListItem>> {
  const db = getDb();
  const { page, limit, search, status, sort, order } = query;
  const offset = (page - 1) * limit;

  const branchId = ctx?.branchScope ?? query.branchId;
  const customerId = ctx?.customerId ?? query.customerId;

  const searchFilter = search
    ? or(
        like(bookings.bookingNumber, `%${search}%`),
        like(users.firstName, `%${search}%`),
        like(users.lastName, `%${search}%`),
        like(users.phone, `%${search}%`),
        like(services.name, `%${search}%`)
      )
    : undefined;

  const whereClause = and(
    status ? eq(bookings.status, status) : undefined,
    branchId ? eq(bookings.branchId, branchId) : undefined,
    customerId ? eq(bookings.customerId, customerId) : undefined,
    query.dateFrom
      ? gte(bookings.scheduledDate, new Date(query.dateFrom))
      : undefined,
    query.dateTo
      ? lte(bookings.scheduledDate, new Date(query.dateTo))
      : undefined,
    searchFilter
  );

  const orderFn = order === "asc" ? asc : desc;
  const orderBy =
    sort === "scheduledDate"
      ? orderFn(bookings.scheduledDate)
      : sort === "totalAmount"
        ? orderFn(bookings.totalAmount)
        : orderFn(bookings.createdAt);

  const [rows, totalResult] = await Promise.all([
    db
      .select({
        id: bookings.id,
        bookingNumber: bookings.bookingNumber,
        customerId: bookings.customerId,
        customerFirstName: users.firstName,
        customerLastName: users.lastName,
        customerPhone: users.phone,
        serviceId: bookings.serviceId,
        serviceName: services.name,
        branchId: bookings.branchId,
        branchName: branches.name,
        status: bookings.status,
        scheduledDate: bookings.scheduledDate,
        scheduledTime: bookings.scheduledTime,
        totalAmount: bookings.totalAmount,
        createdAt: bookings.createdAt,
      })
      .from(bookings)
      .innerJoin(customers, eq(bookings.customerId, customers.id))
      .innerJoin(users, eq(customers.userId, users.id))
      .innerJoin(services, eq(bookings.serviceId, services.id))
      .innerJoin(branches, eq(bookings.branchId, branches.id))
      .where(whereClause)
      .orderBy(orderBy)
      .limit(limit)
      .offset(offset),

    db
      .select({ value: count() })
      .from(bookings)
      .innerJoin(customers, eq(bookings.customerId, customers.id))
      .innerJoin(users, eq(customers.userId, users.id))
      .innerJoin(services, eq(bookings.serviceId, services.id))
      .where(whereClause),
  ]);

  const bookingIds = rows.map((r) => r.id);
  const assignmentMap = new Map<string, string[]>();

  if (bookingIds.length > 0) {
    const assignments = await db
      .select({
        bookingId: bookingAssignments.bookingId,
        firstName: users.firstName,
        lastName: users.lastName,
      })
      .from(bookingAssignments)
      .innerJoin(employees, eq(bookingAssignments.employeeId, employees.id))
      .innerJoin(users, eq(employees.userId, users.id))
      .where(inArray(bookingAssignments.bookingId, bookingIds));

    for (const a of assignments) {
      const name = `${a.firstName} ${a.lastName}`.trim();
      const list = assignmentMap.get(a.bookingId) ?? [];
      list.push(name);
      assignmentMap.set(a.bookingId, list);
    }
  }

  // Staff can only see assigned jobs when they don't manage branch
  let items = rows.map((row) => ({
    id: row.id,
    bookingNumber: row.bookingNumber,
    customerId: row.customerId,
    customerName: `${row.customerFirstName} ${row.customerLastName}`.trim(),
    customerPhone: row.customerPhone,
    serviceId: row.serviceId,
    serviceName: row.serviceName,
    branchId: row.branchId,
    branchName: row.branchName,
    status: row.status,
    scheduledDate: row.scheduledDate.toISOString().slice(0, 10),
    scheduledTime: row.scheduledTime,
    totalAmount: toDecimalNumber(row.totalAmount),
    assignedStaff: assignmentMap.get(row.id) ?? [],
    createdAt: row.createdAt.toISOString(),
  }));

  if (ctx?.employeeId && !ctx.canManageAll && !ctx.canManageBranch) {
    const assigned = await db
      .select({ bookingId: bookingAssignments.bookingId })
      .from(bookingAssignments)
      .where(eq(bookingAssignments.employeeId, ctx.employeeId));
    const allowed = new Set(assigned.map((a) => a.bookingId));
    items = items.filter((i) => allowed.has(i.id));
  }

  const total = totalResult[0]?.value ?? 0;

  return {
    items,
    page,
    limit,
    total,
    totalPages: Math.ceil(total / limit) || 1,
  };
}

export async function getBookingById(id: string): Promise<BookingDetailDto> {
  const db = getDb();

  const rows = await db
    .select({
      id: bookings.id,
      bookingNumber: bookings.bookingNumber,
      customerId: bookings.customerId,
      customerFirstName: users.firstName,
      customerLastName: users.lastName,
      customerPhone: users.phone,
      serviceId: bookings.serviceId,
      serviceName: services.name,
      branchId: bookings.branchId,
      branchName: branches.name,
      status: bookings.status,
      scheduledDate: bookings.scheduledDate,
      scheduledTime: bookings.scheduledTime,
      estimatedDuration: bookings.estimatedDuration,
      customerNotes: bookings.customerNotes,
      internalNotes: bookings.internalNotes,
      subtotal: bookings.subtotal,
      taxRate: bookings.taxRate,
      taxAmount: bookings.taxAmount,
      discount: bookings.discount,
      totalAmount: bookings.totalAmount,
      assignedAt: bookings.assignedAt,
      acceptedAt: bookings.acceptedAt,
      startedAt: bookings.startedAt,
      completedAt: bookings.completedAt,
      cancelledAt: bookings.cancelledAt,
      cancellationReason: bookings.cancellationReason,
      createdAt: bookings.createdAt,
      updatedAt: bookings.updatedAt,
      addressId: addresses.id,
      addressLabel: addresses.label,
      addressLine1: addresses.line1,
      addressLine2: addresses.line2,
      addressCity: addresses.city,
      addressState: addresses.state,
      addressPincode: addresses.pincode,
    })
    .from(bookings)
    .innerJoin(customers, eq(bookings.customerId, customers.id))
    .innerJoin(users, eq(customers.userId, users.id))
    .innerJoin(services, eq(bookings.serviceId, services.id))
    .innerJoin(branches, eq(bookings.branchId, branches.id))
    .innerJoin(addresses, eq(bookings.addressId, addresses.id))
    .where(eq(bookings.id, id))
    .limit(1);

  const row = rows[0];
  if (!row) throw new BookingServiceError("Booking not found", 404);

  const [items, assignments, history] = await Promise.all([
    db.select().from(bookingItems).where(eq(bookingItems.bookingId, id)),
    db
      .select({
        id: bookingAssignments.id,
        employeeId: bookingAssignments.employeeId,
        employeeCode: employees.employeeCode,
        firstName: users.firstName,
        lastName: users.lastName,
        isPrimary: bookingAssignments.isPrimary,
        acceptedAt: bookingAssignments.acceptedAt,
        rejectedAt: bookingAssignments.rejectedAt,
      })
      .from(bookingAssignments)
      .innerJoin(employees, eq(bookingAssignments.employeeId, employees.id))
      .innerJoin(users, eq(employees.userId, users.id))
      .where(eq(bookingAssignments.bookingId, id)),
    db
      .select({
        id: bookingStatusHistory.id,
        fromStatus: bookingStatusHistory.fromStatus,
        toStatus: bookingStatusHistory.toStatus,
        notes: bookingStatusHistory.notes,
        createdAt: bookingStatusHistory.createdAt,
        changedByFirstName: users.firstName,
        changedByLastName: users.lastName,
      })
      .from(bookingStatusHistory)
      .leftJoin(users, eq(bookingStatusHistory.changedById, users.id))
      .where(eq(bookingStatusHistory.bookingId, id))
      .orderBy(desc(bookingStatusHistory.createdAt)),
  ]);

  const assignmentDtos: BookingAssignmentDto[] = assignments.map((a) => ({
    id: a.id,
    employeeId: a.employeeId,
    employeeName: `${a.firstName} ${a.lastName}`.trim(),
    employeeCode: a.employeeCode,
    isPrimary: a.isPrimary,
    acceptedAt: a.acceptedAt?.toISOString() ?? null,
    rejectedAt: a.rejectedAt?.toISOString() ?? null,
  }));

  const historyDtos: BookingStatusHistoryDto[] = history.map((h) => ({
    id: h.id,
    fromStatus: h.fromStatus,
    toStatus: h.toStatus,
    notes: h.notes,
    changedByName:
      h.changedByFirstName && h.changedByLastName
        ? `${h.changedByFirstName} ${h.changedByLastName}`.trim()
        : null,
    createdAt: h.createdAt.toISOString(),
  }));

  const itemDtos: BookingItemDto[] = items.map((i) => ({
    id: i.id,
    serviceItemId: i.serviceItemId,
    name: i.name,
    description: i.description,
    price: toDecimalNumber(i.price),
    quantity: i.quantity,
  }));

  return {
    id: row.id,
    bookingNumber: row.bookingNumber,
    customerId: row.customerId,
    customerName: `${row.customerFirstName} ${row.customerLastName}`.trim(),
    customerPhone: row.customerPhone,
    serviceId: row.serviceId,
    serviceName: row.serviceName,
    branchId: row.branchId,
    branchName: row.branchName,
    status: row.status,
    scheduledDate: row.scheduledDate.toISOString().slice(0, 10),
    scheduledTime: row.scheduledTime,
    estimatedDuration: row.estimatedDuration,
    customerNotes: row.customerNotes,
    internalNotes: row.internalNotes,
    subtotal: toDecimalNumber(row.subtotal),
    taxRate: toDecimalNumber(row.taxRate),
    taxAmount: toDecimalNumber(row.taxAmount),
    discount: toDecimalNumber(row.discount),
    totalAmount: toDecimalNumber(row.totalAmount),
    assignedStaff: assignmentDtos.map((a) => a.employeeName),
    address: {
      id: row.addressId,
      label: row.addressLabel,
      line1: row.addressLine1,
      line2: row.addressLine2,
      city: row.addressCity,
      state: row.addressState,
      pincode: row.addressPincode,
    },
    items: itemDtos,
    assignments: assignmentDtos,
    statusHistory: historyDtos,
    assignedAt: row.assignedAt?.toISOString() ?? null,
    acceptedAt: row.acceptedAt?.toISOString() ?? null,
    startedAt: row.startedAt?.toISOString() ?? null,
    completedAt: row.completedAt?.toISOString() ?? null,
    cancelledAt: row.cancelledAt?.toISOString() ?? null,
    cancellationReason: row.cancellationReason,
    createdAt: row.createdAt.toISOString(),
    updatedAt: row.updatedAt.toISOString(),
  };
}

export async function createBooking(
  input: CreateBookingInput,
  actorUserId: string,
  forcedCustomerId?: string
): Promise<BookingDetailDto> {
  const db = getDb();
  const customerId = forcedCustomerId ?? input.customerId;

  if (!customerId) {
    throw new BookingServiceError("Customer is required", 400);
  }

  const customer = await db.query.customers.findFirst({
    where: eq(customers.id, customerId),
  });
  if (!customer) throw new BookingServiceError("Customer not found", 404);

  const service = await db.query.services.findFirst({
    where: eq(services.id, input.serviceId),
  });
  if (!service || !service.isActive) {
    throw new BookingServiceError("Service not available", 404);
  }

  const address = await db.query.addresses.findFirst({
    where: and(
      eq(addresses.id, input.addressId),
      eq(addresses.customerId, customerId)
    ),
  });
  if (!address) throw new BookingServiceError("Address not found", 404);

  const branch = await db.query.branches.findFirst({
    where: eq(branches.id, input.branchId),
  });
  if (!branch) throw new BookingServiceError("Branch not found", 404);

  let selectedItems: (typeof serviceItems.$inferSelect)[] = [];
  if (input.serviceItemIds.length > 0) {
    selectedItems = await db
      .select()
      .from(serviceItems)
      .where(
        and(
          eq(serviceItems.serviceId, input.serviceId),
          inArray(serviceItems.id, input.serviceItemIds),
          eq(serviceItems.isActive, true)
        )
      );
  }

  const lineItems =
    selectedItems.length > 0
      ? selectedItems.map((item) => ({
          serviceItemId: item.id,
          name: item.name,
          description: item.description,
          price: toDecimalNumber(item.price),
          quantity: 1,
          duration: item.duration ?? 0,
        }))
      : [
          {
            serviceItemId: null as string | null,
            name: service.name,
            description: service.description,
            price: toDecimalNumber(service.basePrice),
            quantity: 1,
            duration: service.duration,
          },
        ];

  const subtotal = lineItems.reduce((sum, i) => sum + i.price * i.quantity, 0);
  const taxAmount = Math.round(((subtotal * TAX_RATE) / 100) * 100) / 100;
  const totalAmount = subtotal + taxAmount;
  const estimatedDuration =
    lineItems.reduce((sum, i) => sum + (i.duration || 0), 0) || service.duration;

  const [inserted] = await db
    .insert(bookings)
    .values({
      bookingNumber: generateBookingNumber(),
      customerId,
      branchId: input.branchId,
      serviceId: input.serviceId,
      addressId: input.addressId,
      status: BookingStatus.PENDING,
      scheduledDate: new Date(input.scheduledDate),
      scheduledTime: input.scheduledTime,
      estimatedDuration,
      customerNotes: input.customerNotes ?? null,
      subtotal: String(subtotal),
      taxRate: String(TAX_RATE),
      taxAmount: String(taxAmount),
      discount: "0",
      totalAmount: String(totalAmount),
    })
    .$returningId();

  if (lineItems.length > 0) {
    await db.insert(bookingItems).values(
      lineItems.map((item) => ({
        bookingId: inserted.id,
        serviceItemId: item.serviceItemId,
        name: item.name,
        description: item.description,
        price: String(item.price),
        quantity: item.quantity,
      }))
    );
  }

  await recordStatusChange(
    inserted.id,
    null,
    BookingStatus.PENDING,
    actorUserId,
    "Booking created"
  );

  const created = await getBookingById(inserted.id);
  const { notifyBookingCreated } = await import(
    "@/server/services/notifications/booking-notifications"
  );
  void notifyBookingCreated(created);
  return created;
}

export async function updateBookingStatus(
  id: string,
  input: UpdateBookingStatusInput,
  actorUserId: string
): Promise<BookingDetailDto> {
  const db = getDb();
  const booking = await getBookingById(id);
  const from = booking.status as BookingStatus;
  const to = input.status;

  assertTransition(from, to);

  const updates: Partial<typeof bookings.$inferInsert> = { status: to };

  if (to === BookingStatus.ACCEPTED) updates.acceptedAt = new Date();
  if (to === BookingStatus.STARTED) updates.startedAt = new Date();
  if (to === BookingStatus.COMPLETED) updates.completedAt = new Date();
  if (to === BookingStatus.CANCELLED) {
    updates.cancelledAt = new Date();
    updates.cancellationReason = input.cancellationReason ?? input.notes ?? null;
  }
  if (to === BookingStatus.ASSIGNED) updates.assignedAt = new Date();

  await db.update(bookings).set(updates).where(eq(bookings.id, id));
  await recordStatusChange(id, from, to, actorUserId, input.notes);

  // Cash-only: record payment when job is completed
  if (to === BookingStatus.COMPLETED) {
    const existingPayment = await db.query.payments.findFirst({
      where: and(
        eq(payments.bookingId, id),
        eq(payments.status, "COMPLETED")
      ),
    });

    if (!existingPayment) {
      await db.insert(payments).values({
        bookingId: id,
        customerId: booking.customerId,
        amount: String(booking.totalAmount),
        method: "CASH",
        status: "COMPLETED",
        paidAt: new Date(),
      });
    }
  }

  const updated = await getBookingById(id);
  const { notifyBookingStatusChanged } = await import(
    "@/server/services/notifications/booking-notifications"
  );
  void notifyBookingStatusChanged(updated, to);
  return updated;
}

export async function assignStaffToBooking(
  id: string,
  input: AssignStaffInput,
  actorUserId: string
): Promise<BookingDetailDto> {
  const db = getDb();
  const booking = await getBookingById(id);

  if (
    booking.status !== BookingStatus.CONFIRMED &&
    booking.status !== BookingStatus.PENDING &&
    booking.status !== BookingStatus.REJECTED &&
    booking.status !== BookingStatus.ASSIGNED
  ) {
    throw new BookingServiceError(
      "Staff can only be assigned when booking is pending, confirmed, assigned, or rejected",
      400
    );
  }

  const staff = await db
    .select()
    .from(employees)
    .where(inArray(employees.id, input.employeeIds));

  if (staff.length !== input.employeeIds.length) {
    throw new BookingServiceError("One or more employees not found", 404);
  }

  for (const emp of staff) {
    if (emp.branchId !== booking.branchId) {
      throw new BookingServiceError(
        "All assigned staff must belong to the booking branch",
        400
      );
    }
  }

  await db
    .delete(bookingAssignments)
    .where(eq(bookingAssignments.bookingId, id));

  const primaryId = input.primaryEmployeeId ?? input.employeeIds[0];

  await db.insert(bookingAssignments).values(
    input.employeeIds.map((employeeId) => ({
      bookingId: id,
      employeeId,
      assignedById: actorUserId,
      isPrimary: employeeId === primaryId,
    }))
  );

  const from = booking.status as BookingStatus;
  if (from !== BookingStatus.ASSIGNED) {
    assertTransition(
      from === BookingStatus.PENDING ? BookingStatus.CONFIRMED : from,
      BookingStatus.ASSIGNED
    );

    // Auto-confirm if still pending, then assign
    if (from === BookingStatus.PENDING) {
      await db
        .update(bookings)
        .set({ status: BookingStatus.CONFIRMED })
        .where(eq(bookings.id, id));
      await recordStatusChange(
        id,
        BookingStatus.PENDING,
        BookingStatus.CONFIRMED,
        actorUserId,
        "Auto-confirmed before assignment"
      );
    }

    const current = from === BookingStatus.PENDING ? BookingStatus.CONFIRMED : from;
    await db
      .update(bookings)
      .set({
        status: BookingStatus.ASSIGNED,
        assignedAt: new Date(),
      })
      .where(eq(bookings.id, id));
    await recordStatusChange(
      id,
      current,
      BookingStatus.ASSIGNED,
      actorUserId,
      "Staff assigned"
    );
  }

  const assigned = await getBookingById(id);
  const { notifyBookingAssigned } = await import(
    "@/server/services/notifications/booking-notifications"
  );
  void notifyBookingAssigned(assigned);
  return assigned;
}

export async function cancelBooking(
  id: string,
  actorUserId: string,
  reason?: string | null
): Promise<BookingDetailDto> {
  return updateBookingStatus(
    id,
    {
      status: BookingStatus.CANCELLED,
      notes: reason,
      cancellationReason: reason,
    },
    actorUserId
  );
}

export async function assertBookingReadable(
  booking: BookingDetailDto,
  ctx: BookingAccessContext
) {
  if (ctx.canManageAll) return;

  if (ctx.customerId && booking.customerId === ctx.customerId) return;

  if (ctx.branchScope && booking.branchId === ctx.branchScope && ctx.canManageBranch) {
    return;
  }

  if (ctx.employeeId) {
    const assigned = booking.assignments.some(
      (a) => a.employeeId === ctx.employeeId
    );
    if (assigned) return;
  }

  throw new BookingServiceError("Booking not found", 404);
}
