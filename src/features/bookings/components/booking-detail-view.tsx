"use client";

import Link from "next/link";
import { useRouter } from "next/navigation";
import { toast } from "sonner";
import { ArrowLeft, MapPin, Users, History } from "lucide-react";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Separator } from "@/components/ui/separator";
import { AssignStaffForm } from "@/features/bookings/components/assign-staff-form";
import type { BookingDetailDto } from "@/server/dto/booking.dto";
import type { EmployeeListItem } from "@/server/dto/employee.dto";
import {
  BookingStatus,
  BOOKING_STATUS_LABELS,
  BOOKING_STATUS_TRANSITIONS,
} from "@/types/booking";
import { formatCurrency } from "@/lib/utils/format";

interface BookingDetailViewProps {
  booking: BookingDetailDto;
  employees?: EmployeeListItem[];
  basePath?: string;
  canManage?: boolean;
  canUpdateStatus?: boolean;
  /** When true, only cancel action is shown for status updates */
  customerView?: boolean;
}

export function BookingDetailView({
  booking,
  employees = [],
  basePath = "/admin/bookings",
  canManage = true,
  canUpdateStatus = true,
  customerView = false,
}: BookingDetailViewProps) {
  const router = useRouter();
  const current = booking.status as BookingStatus;
  const nextStatuses = (
    BOOKING_STATUS_TRANSITIONS[current] ?? []
  ).filter((s) => (customerView ? s === BookingStatus.CANCELLED : true));

  async function changeStatus(status: BookingStatus) {
    const notes =
      status === BookingStatus.CANCELLED
        ? prompt("Cancellation reason (optional)") ?? undefined
        : undefined;

    try {
      const res = await fetch(`/api/bookings/${booking.id}/status`, {
        method: "PATCH",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          status,
          notes,
          cancellationReason: notes,
        }),
      });
      const json = await res.json();
      if (!res.ok) throw new Error(json.message ?? "Status update failed");
      toast.success(`Status updated to ${BOOKING_STATUS_LABELS[status]}`);
      router.refresh();
    } catch (error) {
      toast.error(error instanceof Error ? error.message : "Something went wrong");
    }
  }

  return (
    <div className="space-y-6">
      <div className="flex flex-wrap items-center gap-4">
        <Button variant="ghost" size="icon" asChild>
          <Link href={basePath}>
            <ArrowLeft className="size-4" />
          </Link>
        </Button>
        <div>
          <h2 className="text-2xl font-bold tracking-tight">
            {booking.bookingNumber}
          </h2>
          <p className="text-muted-foreground">
            {booking.serviceName} · {booking.branchName}
          </p>
        </div>
        <Badge className="ml-auto" variant="outline">
          {BOOKING_STATUS_LABELS[current] ?? booking.status}
        </Badge>
      </div>

      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-sm text-muted-foreground">Customer</CardTitle>
          </CardHeader>
          <CardContent className="text-sm">
            <p className="font-medium">{booking.customerName}</p>
            <p className="text-muted-foreground">{booking.customerPhone}</p>
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-sm text-muted-foreground">Schedule</CardTitle>
          </CardHeader>
          <CardContent className="text-sm">
            <p className="font-medium">{booking.scheduledDate}</p>
            <p className="text-muted-foreground">
              {booking.scheduledTime} · {booking.estimatedDuration} min
            </p>
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-sm text-muted-foreground">Total</CardTitle>
          </CardHeader>
          <CardContent className="text-2xl font-bold">
            {formatCurrency(booking.totalAmount)}
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-sm text-muted-foreground">Staff</CardTitle>
          </CardHeader>
          <CardContent className="text-sm">
            {booking.assignedStaff.length === 0
              ? "Unassigned"
              : booking.assignedStaff.join(", ")}
          </CardContent>
        </Card>
      </div>

      <div className="grid gap-6 lg:grid-cols-2">
        <Card>
          <CardHeader>
            <CardTitle className="flex items-center gap-2 text-base">
              <MapPin className="size-4" />
              Service Address
            </CardTitle>
          </CardHeader>
          <CardContent className="text-sm">
            <p className="font-medium">{booking.address.label}</p>
            <p>{booking.address.line1}</p>
            {booking.address.line2 && <p>{booking.address.line2}</p>}
            <p>
              {booking.address.city}, {booking.address.state}{" "}
              {booking.address.pincode}
            </p>
            {booking.customerNotes && (
              <>
                <Separator className="my-3" />
                <p className="text-muted-foreground">
                  Notes: {booking.customerNotes}
                </p>
              </>
            )}
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle className="text-base">Line Items</CardTitle>
          </CardHeader>
          <CardContent className="space-y-2 text-sm">
            {booking.items.map((item) => (
              <div key={item.id} className="flex justify-between">
                <span>
                  {item.name} × {item.quantity}
                </span>
                <span>{formatCurrency(item.price * item.quantity)}</span>
              </div>
            ))}
            <Separator />
            <div className="flex justify-between text-muted-foreground">
              <span>Subtotal</span>
              <span>{formatCurrency(booking.subtotal)}</span>
            </div>
            <div className="flex justify-between text-muted-foreground">
              <span>Tax ({booking.taxRate}%)</span>
              <span>{formatCurrency(booking.taxAmount)}</span>
            </div>
            <div className="flex justify-between font-medium">
              <span>Total</span>
              <span>{formatCurrency(booking.totalAmount)}</span>
            </div>
          </CardContent>
        </Card>
      </div>

      {canUpdateStatus && nextStatuses.length > 0 && (
        <Card>
          <CardHeader>
            <CardTitle className="text-base">Update Status</CardTitle>
          </CardHeader>
          <CardContent className="flex flex-wrap gap-2">
            {nextStatuses.map((status) => (
              <Button
                key={status}
                variant={
                  status === BookingStatus.CANCELLED ||
                  status === BookingStatus.REJECTED
                    ? "destructive"
                    : "outline"
                }
                size="sm"
                onClick={() => changeStatus(status)}
              >
                Mark {BOOKING_STATUS_LABELS[status]}
              </Button>
            ))}
          </CardContent>
        </Card>
      )}

      <div className="grid gap-6 lg:grid-cols-2">
        {canManage && (
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2 text-base">
                <Users className="size-4" />
                Staff Assignment
              </CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
              {booking.assignments.length > 0 && (
                <div className="space-y-2">
                  {booking.assignments.map((a) => (
                    <div
                      key={a.id}
                      className="flex items-center justify-between rounded-lg border p-3 text-sm"
                    >
                      <div>
                        <p className="font-medium">{a.employeeName}</p>
                        <p className="text-xs text-muted-foreground">
                          {a.employeeCode}
                          {a.isPrimary && " · Primary"}
                        </p>
                      </div>
                      <Badge variant="outline">
                        {a.acceptedAt
                          ? "Accepted"
                          : a.rejectedAt
                            ? "Rejected"
                            : "Pending"}
                      </Badge>
                    </div>
                  ))}
                </div>
              )}
              <AssignStaffForm
                bookingId={booking.id}
                employees={employees.filter(
                  (e) => e.branchId === booking.branchId
                )}
              />
            </CardContent>
          </Card>
        )}

        <Card>
          <CardHeader>
            <CardTitle className="flex items-center gap-2 text-base">
              <History className="size-4" />
              Status History
            </CardTitle>
          </CardHeader>
          <CardContent className="space-y-3">
            {booking.statusHistory.length === 0 ? (
              <p className="text-sm text-muted-foreground">No history yet</p>
            ) : (
              booking.statusHistory.map((h) => (
                <div key={h.id} className="border-l-2 border-primary/30 pl-3 text-sm">
                  <p className="font-medium">
                    {h.fromStatus
                      ? `${BOOKING_STATUS_LABELS[h.fromStatus as BookingStatus] ?? h.fromStatus} → `
                      : ""}
                    {BOOKING_STATUS_LABELS[h.toStatus as BookingStatus] ??
                      h.toStatus}
                  </p>
                  <p className="text-xs text-muted-foreground">
                    {new Date(h.createdAt).toLocaleString("en-IN")}
                    {h.changedByName && ` · ${h.changedByName}`}
                  </p>
                  {h.notes && (
                    <p className="text-xs text-muted-foreground">{h.notes}</p>
                  )}
                </div>
              ))
            )}
          </CardContent>
        </Card>
      </div>
    </div>
  );
}
