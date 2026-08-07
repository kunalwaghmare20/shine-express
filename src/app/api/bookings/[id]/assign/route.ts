import { NextRequest } from "next/server";
import {
  ForbiddenError,
  requireBookingManageAccess,
} from "@/lib/auth";
import { apiSuccess, handleApiError } from "@/lib/api";
import { assignStaffSchema } from "@/features/bookings/validators/booking.schema";
import {
  assertBookingReadable,
  assignStaffToBooking,
  getBookingById,
} from "@/server/services/booking.service";

type RouteParams = { params: Promise<{ id: string }> };

export async function POST(request: NextRequest, { params }: RouteParams) {
  try {
    const ctx = await requireBookingManageAccess();
    const { id } = await params;
    const booking = await getBookingById(id);
    await assertBookingReadable(booking, ctx);

    if (ctx.branchScope && booking.branchId !== ctx.branchScope) {
      throw new ForbiddenError("Cannot assign staff outside your branch");
    }

    const body = await request.json();
    const input = assignStaffSchema.parse(body);
    const result = await assignStaffToBooking(id, input, ctx.user.id);
    return apiSuccess(result);
  } catch (error) {
    return handleApiError(error);
  }
}
