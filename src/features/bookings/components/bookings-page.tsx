import { Suspense } from "react";
import { bookingListQuerySchema } from "@/features/bookings/validators/booking.schema";
import { BookingsTable } from "@/features/bookings/components/bookings-table";
import { listBookings } from "@/server/services/booking.service";
import type { BookingAccessContext } from "@/lib/auth/booking-access";
import { Skeleton } from "@/components/ui/skeleton";

interface BookingsPageProps {
  searchParams: Promise<Record<string, string | string[] | undefined>>;
  basePath?: string;
  access?: BookingAccessContext;
}

async function BookingsContent({
  searchParams,
  basePath,
  access,
}: BookingsPageProps) {
  const params = await searchParams;
  const query = bookingListQuerySchema.parse({
    page: params.page,
    limit: params.limit,
    search: params.search,
    status: params.status,
    branchId: params.branchId,
    sort: params.sort,
    order: params.order,
  });

  const data = await listBookings(query, access);

  return <BookingsTable data={data} basePath={basePath} />;
}

function BookingsSkeleton() {
  return (
    <div className="space-y-4">
      <Skeleton className="h-10 w-full max-w-sm" />
      <Skeleton className="h-64 w-full" />
    </div>
  );
}

export function BookingsPage(props: BookingsPageProps) {
  return (
    <div className="space-y-6">
      <div>
        <h2 className="text-2xl font-bold tracking-tight">Bookings</h2>
        <p className="text-muted-foreground">
          Manage bookings, assign staff, and track job status.
        </p>
      </div>

      <Suspense fallback={<BookingsSkeleton />}>
        <BookingsContent {...props} />
      </Suspense>
    </div>
  );
}
