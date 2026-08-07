import { NextRequest } from "next/server";
import { requireBookingAccess } from "@/lib/auth";
import { apiSuccess, handleApiError } from "@/lib/api";
import { updateBookingStatusSchema } from "@/features/bookings/validators/booking.schema";
import {
  assertBookingReadable,
  getBookingById,
  updateBookingStatus,
} from "@/server/services/booking.service";
import { BookingStatus } from "@/types/booking";
import { ForbiddenError } from "@/lib/auth";

type RouteParams = { params: Promise<{ id: string }> };

export async function PATCH(request: NextRequest, { params }: RouteParams) {
  try {
    const ctx = await requireBookingAccess();
    const { id } = await params;
    const booking = await getBookingById(id);
    await assertBookingReadable(booking, ctx);

    const body = await request.json();
    const input = updateBookingStatusSchema.parse(body);

    // Customers may only cancel
    if (ctx.customerId && !ctx.canManageAll && !ctx.canManageBranch) {
      if (input.status !== BookingStatus.CANCELLED) {
        throw new ForbiddenError("Customers can only cancel bookings");
      }
      if (booking.customerId !== ctx.customerId) {
        throw new ForbiddenError("Forbidden");
      }
    }

    // Staff may update job statuses on assigned bookings
    if (
      ctx.employeeId &&
      !ctx.canManageAll &&
      !ctx.canManageBranch &&
      !ctx.canUpdateJob
    ) {
      throw new ForbiddenError("Cannot update job status");
    }

    const result = await updateBookingStatus(id, input, ctx.user.id);
    return apiSuccess(result);
  } catch (error) {
    return handleApiError(error);
  }
}
