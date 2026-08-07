import Link from "next/link";
import { notFound } from "next/navigation";
import {
  assertBookingReadable,
  getBookingById,
  BookingServiceError,
} from "@/server/services/booking.service";
import { requireBookingAccess } from "@/lib/auth";
import { BookingDetailView } from "@/features/bookings/components/booking-detail-view";
import { Button } from "@/components/ui/button";
import {
  BookingStatus,
  BOOKING_STATUS_TRANSITIONS,
} from "@/types/booking";
import { StaffJobActions } from "@/features/bookings/components/staff-job-actions";

export default async function StaffJobDetailPage({
  params,
}: {
  params: Promise<{ id: string }>;
}) {
  const { id } = await params;
  const access = await requireBookingAccess();

  try {
    const booking = await getBookingById(id);
    await assertBookingReadable(booking, access);

    const nextStatuses =
      BOOKING_STATUS_TRANSITIONS[booking.status as BookingStatus] ?? [];

    return (
      <div className="space-y-4">
        <Button variant="outline" size="sm" asChild>
          <Link href="/staff/jobs">Back to jobs</Link>
        </Button>
        <BookingDetailView
          booking={booking}
          basePath="/staff/jobs"
          canManage={false}
          canUpdateStatus={false}
        />
        {access.canUpdateJob && nextStatuses.length > 0 && (
          <StaffJobActions bookingId={booking.id} nextStatuses={nextStatuses} />
        )}
      </div>
    );
  } catch (error) {
    if (error instanceof BookingServiceError && error.statusCode === 404) {
      notFound();
    }
    throw error;
  }
}
