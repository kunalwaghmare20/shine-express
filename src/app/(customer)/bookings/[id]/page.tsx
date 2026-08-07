import { notFound } from "next/navigation";
import {
  assertBookingReadable,
  getBookingById,
  BookingServiceError,
} from "@/server/services/booking.service";
import { requireBookingAccess } from "@/lib/auth";
import { BookingDetailView } from "@/features/bookings/components/booking-detail-view";

export default async function CustomerBookingDetailPage({
  params,
}: {
  params: Promise<{ id: string }>;
}) {
  const { id } = await params;
  const access = await requireBookingAccess();

  try {
    const booking = await getBookingById(id);
    await assertBookingReadable(booking, access);

    return (
      <BookingDetailView
        booking={booking}
        basePath="/bookings"
        canManage={false}
        canUpdateStatus
        customerView
      />
    );
  } catch (error) {
    if (error instanceof BookingServiceError && error.statusCode === 404) {
      notFound();
    }
    throw error;
  }
}
