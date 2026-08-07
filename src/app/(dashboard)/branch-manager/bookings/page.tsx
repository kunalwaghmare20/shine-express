import { requireBookingManageAccess } from "@/lib/auth";
import { BookingsPage } from "@/features/bookings/components/bookings-page";

export default async function BranchManagerBookingsPage({
  searchParams,
}: {
  searchParams: Promise<Record<string, string | string[] | undefined>>;
}) {
  const access = await requireBookingManageAccess();

  return (
    <BookingsPage
      searchParams={searchParams}
      basePath="/branch-manager/bookings"
      access={access}
    />
  );
}
