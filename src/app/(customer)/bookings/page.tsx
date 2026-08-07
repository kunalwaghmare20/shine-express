import { requireBookingAccess } from "@/lib/auth";
import { BookingsPage } from "@/features/bookings/components/bookings-page";

export default async function CustomerBookingsPage({
  searchParams,
}: {
  searchParams: Promise<Record<string, string | string[] | undefined>>;
}) {
  const access = await requireBookingAccess();

  return (
    <div className="space-y-6">
      <BookingsPage
        searchParams={searchParams}
        basePath="/bookings"
        access={access}
      />
    </div>
  );
}
