import { NextRequest } from "next/server";
import {
  ForbiddenError,
  requireBookingAccess,
} from "@/lib/auth";
import { apiSuccess, handleApiError } from "@/lib/api";
import {
  assertBookingReadable,
  getBookingById,
} from "@/server/services/booking.service";
import { cancelBooking } from "@/server/services/booking.service";
import { Permission, UserRole, hasPermission } from "@/config/roles";

type RouteParams = { params: Promise<{ id: string }> };

export async function GET(_request: NextRequest, { params }: RouteParams) {
  try {
    const ctx = await requireBookingAccess();
    const { id } = await params;
    const booking = await getBookingById(id);
    await assertBookingReadable(booking, ctx);
    return apiSuccess(booking);
  } catch (error) {
    return handleApiError(error);
  }
}

export async function DELETE(request: NextRequest, { params }: RouteParams) {
  try {
    const ctx = await requireBookingAccess();
    const { id } = await params;
    const booking = await getBookingById(id);
    await assertBookingReadable(booking, ctx);

    const role = ctx.user.role as UserRole;
    const isOwn =
      ctx.customerId && booking.customerId === ctx.customerId;
    const canCancelOwn =
      isOwn && hasPermission(role, Permission.CANCEL_OWN_BOOKING);
    const canManage = ctx.canManageAll || ctx.canManageBranch;

    if (!canCancelOwn && !canManage) {
      throw new ForbiddenError("Cannot cancel this booking");
    }

    const body = await request.json().catch(() => ({}));
    const result = await cancelBooking(
      id,
      ctx.user.id,
      body.reason ?? body.cancellationReason
    );
    return apiSuccess(result);
  } catch (error) {
    return handleApiError(error);
  }
}
