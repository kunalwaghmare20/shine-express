"use client";

import Link from "next/link";
import { useRouter, useSearchParams } from "next/navigation";
import { useCallback, useTransition } from "react";
import { Search } from "lucide-react";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import type { BookingListItem } from "@/server/dto/booking.dto";
import type { PaginatedResult } from "@/server/dto/pagination.dto";
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

interface BookingsTableProps {
  data: PaginatedResult<BookingListItem>;
  basePath?: string;
}

export function BookingsTable({
  data,
  basePath = "/admin/bookings",
}: BookingsTableProps) {
  const router = useRouter();
  const searchParams = useSearchParams();
  const [isPending, startTransition] = useTransition();
  const search = searchParams.get("search") ?? "";
  const status = searchParams.get("status") ?? "";

  const updateParams = useCallback(
    (updates: Record<string, string | null>) => {
      const params = new URLSearchParams(searchParams.toString());
      Object.entries(updates).forEach(([key, value]) => {
        if (value === null || value === "") params.delete(key);
        else params.set(key, value);
      });
      startTransition(() => {
        router.push(`${basePath}?${params.toString()}`);
      });
    },
    [basePath, router, searchParams]
  );

  return (
    <div className="space-y-4">
      <div className="flex flex-col gap-3 sm:flex-row sm:items-center">
        <form
          className="relative w-full max-w-sm"
          onSubmit={(e) => {
            e.preventDefault();
            const formData = new FormData(e.currentTarget);
            updateParams({
              search: (formData.get("search") as string) || null,
              page: "1",
            });
          }}
        >
          <Search className="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
          <Input
            name="search"
            defaultValue={search}
            placeholder="Search booking #, customer..."
            className="pl-9"
          />
        </form>
        <select
          className="flex h-9 rounded-md border border-input bg-transparent px-3 text-sm"
          value={status}
          onChange={(e) =>
            updateParams({ status: e.target.value || null, page: "1" })
          }
        >
          <option value="">All statuses</option>
          {Object.values(BookingStatus).map((s) => (
            <option key={s} value={s}>
              {BOOKING_STATUS_LABELS[s]}
            </option>
          ))}
        </select>
      </div>

      <div className="overflow-hidden rounded-xl border">
        <table className="w-full text-sm">
          <thead className="border-b bg-muted/40">
            <tr className="text-left text-muted-foreground">
              <th className="px-4 py-3 font-medium">Booking</th>
              <th className="px-4 py-3 font-medium">Customer</th>
              <th className="px-4 py-3 font-medium">Service</th>
              <th className="px-4 py-3 font-medium">Schedule</th>
              <th className="px-4 py-3 font-medium">Amount</th>
              <th className="px-4 py-3 font-medium">Status</th>
              <th className="px-4 py-3 font-medium" />
            </tr>
          </thead>
          <tbody>
            {data.items.length === 0 ? (
              <tr>
                <td
                  colSpan={7}
                  className="px-4 py-12 text-center text-muted-foreground"
                >
                  No bookings found
                </td>
              </tr>
            ) : (
              data.items.map((booking) => (
                <tr key={booking.id} className="border-b last:border-0">
                  <td className="px-4 py-3">
                    <div className="font-medium">{booking.bookingNumber}</div>
                    <div className="text-xs text-muted-foreground">
                      {booking.branchName}
                    </div>
                  </td>
                  <td className="px-4 py-3">
                    <div>{booking.customerName}</div>
                    <div className="text-xs text-muted-foreground">
                      {booking.customerPhone}
                    </div>
                  </td>
                  <td className="px-4 py-3">{booking.serviceName}</td>
                  <td className="px-4 py-3">
                    {booking.scheduledDate}
                    <div className="text-xs text-muted-foreground">
                      {booking.scheduledTime}
                    </div>
                  </td>
                  <td className="px-4 py-3">
                    {formatCurrency(booking.totalAmount)}
                  </td>
                  <td className="px-4 py-3">
                    <Badge variant={STATUS_VARIANT[booking.status] ?? "outline"}>
                      {BOOKING_STATUS_LABELS[booking.status as BookingStatus] ??
                        booking.status}
                    </Badge>
                  </td>
                  <td className="px-4 py-3 text-right">
                    <Button variant="outline" size="sm" asChild>
                      <Link href={`${basePath}/${booking.id}`}>View</Link>
                    </Button>
                  </td>
                </tr>
              ))
            )}
          </tbody>
        </table>
      </div>

      <div className="flex items-center justify-between">
        <p className="text-sm text-muted-foreground">
          Page {data.page} of {data.totalPages} · {data.total} bookings
          {isPending && " · Loading..."}
        </p>
        <div className="flex gap-2">
          <Button
            variant="outline"
            size="sm"
            disabled={data.page <= 1 || isPending}
            onClick={() => updateParams({ page: String(data.page - 1) })}
          >
            Previous
          </Button>
          <Button
            variant="outline"
            size="sm"
            disabled={data.page >= data.totalPages || isPending}
            onClick={() => updateParams({ page: String(data.page + 1) })}
          >
            Next
          </Button>
        </div>
      </div>
    </div>
  );
}
