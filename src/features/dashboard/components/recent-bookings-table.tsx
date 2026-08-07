import Link from "next/link";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Skeleton } from "@/components/ui/skeleton";
import type { RecentBookingRow } from "@/server/dto/dashboard.dto";
import { BookingStatus, BOOKING_STATUS_LABELS } from "@/types/booking";
import { formatCurrency } from "@/lib/utils/format";

const STATUS_VARIANT: Record<
  string,
  "default" | "secondary" | "success" | "warning" | "destructive" | "outline"
> = {
  [BookingStatus.PENDING]: "warning",
  [BookingStatus.CONFIRMED]: "secondary",
  [BookingStatus.ASSIGNED]: "secondary",
  [BookingStatus.ACCEPTED]: "default",
  [BookingStatus.ON_THE_WAY]: "default",
  [BookingStatus.STARTED]: "default",
  [BookingStatus.COMPLETED]: "success",
  [BookingStatus.CANCELLED]: "destructive",
  [BookingStatus.REJECTED]: "destructive",
};

interface RecentBookingsTableProps {
  bookings: RecentBookingRow[];
}

export function RecentBookingsTable({ bookings }: RecentBookingsTableProps) {
  return (
    <Card className="col-span-full">
      <CardHeader className="flex flex-row items-center justify-between">
        <CardTitle>Recent Bookings</CardTitle>
        <Button variant="outline" size="sm" asChild>
          <Link href="/admin/bookings">View all</Link>
        </Button>
      </CardHeader>
      <CardContent>
        {bookings.length === 0 ? (
          <p className="py-8 text-center text-sm text-muted-foreground">
            No bookings yet. They will appear here once customers start booking.
          </p>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b text-left text-muted-foreground">
                  <th className="pb-3 pr-4 font-medium">Booking #</th>
                  <th className="pb-3 pr-4 font-medium">Customer</th>
                  <th className="pb-3 pr-4 font-medium">Service</th>
                  <th className="pb-3 pr-4 font-medium">Date</th>
                  <th className="pb-3 pr-4 font-medium">Amount</th>
                  <th className="pb-3 font-medium">Status</th>
                </tr>
              </thead>
              <tbody>
                {bookings.map((booking) => (
                  <tr key={booking.id} className="border-b last:border-0">
                    <td className="py-3 pr-4 font-medium">
                      {booking.bookingNumber}
                    </td>
                    <td className="py-3 pr-4">{booking.customerName}</td>
                    <td className="py-3 pr-4">{booking.serviceName}</td>
                    <td className="py-3 pr-4">{booking.scheduledDate}</td>
                    <td className="py-3 pr-4">
                      {formatCurrency(booking.totalAmount)}
                    </td>
                    <td className="py-3">
                      <Badge
                        variant={
                          STATUS_VARIANT[booking.status] ?? "outline"
                        }
                      >
                        {BOOKING_STATUS_LABELS[
                          booking.status as BookingStatus
                        ] ?? booking.status}
                      </Badge>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </CardContent>
    </Card>
  );
}

export function RecentBookingsTableSkeleton() {
  return (
    <Card className="col-span-full">
      <CardHeader>
        <Skeleton className="h-6 w-40" />
      </CardHeader>
      <CardContent className="space-y-3">
        {Array.from({ length: 5 }).map((_, i) => (
          <Skeleton key={i} className="h-10 w-full" />
        ))}
      </CardContent>
    </Card>
  );
}
