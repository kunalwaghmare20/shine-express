import { notFound } from "next/navigation";
import {
  assertBookingReadable,
  getBookingById,
  BookingServiceError,
} from "@/server/services/booking.service";
import { listEmployees } from "@/server/services/employee.service";
import { requireBookingManageAccess } from "@/lib/auth";
import { BookingDetailView } from "@/features/bookings/components/booking-detail-view";

export default async function AdminBookingDetailPage({
  params,
}: {
  params: Promise<{ id: string }>;
}) {
  const { id } = await params;
  const access = await requireBookingManageAccess();

  try {
    const [booking, employees] = await Promise.all([
      getBookingById(id),
      listEmployees({ page: 1, limit: 100, sort: "firstName", order: "asc" }),
    ]);
    await assertBookingReadable(booking, access);

    return (
      <BookingDetailView
        booking={booking}
        employees={employees.items}
        canManage
        canUpdateStatus
      />
    );
  } catch (error) {
    if (error instanceof BookingServiceError && error.statusCode === 404) {
      notFound();
    }
    throw error;
  }
}
