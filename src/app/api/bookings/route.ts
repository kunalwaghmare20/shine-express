import { NextRequest } from "next/server";
import {
  ForbiddenError,
  requireBookingAccess,
} from "@/lib/auth";
import { apiSuccess, handleApiError } from "@/lib/api";
import { getPaginationMeta } from "@/lib/api/pagination";
import {
  bookingListQuerySchema,
  createBookingSchema,
} from "@/features/bookings/validators/booking.schema";
import {
  createBooking,
  listBookings,
} from "@/server/services/booking.service";
import { Permission, UserRole, hasPermission } from "@/config/roles";

export async function GET(request: NextRequest) {
  try {
    const ctx = await requireBookingAccess();
    const params = Object.fromEntries(request.nextUrl.searchParams);
    const query = bookingListQuerySchema.parse(params);

    if (ctx.customerId) {
      query.customerId = ctx.customerId;
    }

    const result = await listBookings(query, ctx);
    return apiSuccess(
      result.items,
      200,
      getPaginationMeta(result.total, result.page, result.limit)
    );
  } catch (error) {
    return handleApiError(error);
  }
}

export async function POST(request: NextRequest) {
  try {
    const ctx = await requireBookingAccess();
    const role = ctx.user.role as UserRole;

    if (
      !hasPermission(role, Permission.CREATE_BOOKING) &&
      !ctx.canManageAll &&
      !ctx.canManageBranch
    ) {
      throw new ForbiddenError("Cannot create bookings");
    }

    const body = await request.json();
    const input = createBookingSchema.parse({
      ...body,
      serviceItemIds: body.serviceItemIds ?? [],
    });

    const customerId = ctx.customerId ?? input.customerId;
    if (!customerId) {
      throw new ForbiddenError("Customer is required");
    }

    if (ctx.customerId && customerId !== ctx.customerId) {
      throw new ForbiddenError("Cannot book for another customer");
    }

    const booking = await createBooking(input, ctx.user.id, customerId);
    return apiSuccess(booking, 201);
  } catch (error) {
    return handleApiError(error);
  }
}
