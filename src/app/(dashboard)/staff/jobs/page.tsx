import Link from "next/link";
import { requireBookingAccess } from "@/lib/auth";
import { listBookings } from "@/server/services/booking.service";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { BookingStatus, BOOKING_STATUS_LABELS } from "@/types/booking";
import { formatCurrency } from "@/lib/utils/format";

export default async function StaffJobsPage() {
  const access = await requireBookingAccess();
  const data = await listBookings(
    { page: 1, limit: 50, sort: "scheduledDate", order: "asc" },
    access
  );

  return (
    <div className="space-y-6">
      <div>
        <h2 className="text-2xl font-bold tracking-tight">Assigned Jobs</h2>
        <p className="text-muted-foreground">
          View and update your assigned service jobs.
        </p>
      </div>

      {data.items.length === 0 ? (
        <Card>
          <CardContent className="py-10 text-center text-muted-foreground">
            No jobs assigned yet.
          </CardContent>
        </Card>
      ) : (
        <div className="grid gap-4">
          {data.items.map((job) => (
            <Card key={job.id}>
              <CardHeader className="flex flex-row items-start justify-between space-y-0">
                <div>
                  <CardTitle className="text-base">{job.bookingNumber}</CardTitle>
                  <p className="text-sm text-muted-foreground">
                    {job.serviceName} · {job.customerName}
                  </p>
                </div>
                <Badge variant="outline">
                  {BOOKING_STATUS_LABELS[job.status as BookingStatus] ??
                    job.status}
                </Badge>
              </CardHeader>
              <CardContent className="flex items-center justify-between">
                <div className="text-sm">
                  <p>
                    {job.scheduledDate} at {job.scheduledTime}
                  </p>
                  <p className="text-muted-foreground">
                    {formatCurrency(job.totalAmount)}
                  </p>
                </div>
                <Button size="sm" asChild>
                  <Link href={`/staff/jobs/${job.id}`}>Open</Link>
                </Button>
              </CardContent>
            </Card>
          ))}
        </div>
      )}
    </div>
  );
}
