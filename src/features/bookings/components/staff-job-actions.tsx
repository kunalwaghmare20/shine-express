"use client";

import { useRouter } from "next/navigation";
import { toast } from "sonner";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import {
  BookingStatus,
  BOOKING_STATUS_LABELS,
} from "@/types/booking";

interface StaffJobActionsProps {
  bookingId: string;
  nextStatuses: BookingStatus[];
}

export function StaffJobActions({
  bookingId,
  nextStatuses,
}: StaffJobActionsProps) {
  const router = useRouter();

  async function changeStatus(status: BookingStatus) {
    try {
      const res = await fetch(`/api/bookings/${bookingId}/status`, {
        method: "PATCH",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ status }),
      });
      const json = await res.json();
      if (!res.ok) throw new Error(json.message ?? "Update failed");
      toast.success(`Marked as ${BOOKING_STATUS_LABELS[status]}`);
      router.refresh();
    } catch (error) {
      toast.error(error instanceof Error ? error.message : "Something went wrong");
    }
  }

  return (
    <Card>
      <CardHeader>
        <CardTitle className="text-base">Job Actions</CardTitle>
      </CardHeader>
      <CardContent className="flex flex-wrap gap-2">
        {nextStatuses.map((status) => (
          <Button
            key={status}
            size="sm"
            variant={
              status === BookingStatus.REJECTED ? "destructive" : "default"
            }
            onClick={() => changeStatus(status)}
          >
            {BOOKING_STATUS_LABELS[status]}
          </Button>
        ))}
      </CardContent>
    </Card>
  );
}
