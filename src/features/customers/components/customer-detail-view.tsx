import Link from "next/link";
import { ArrowLeft, MapPin, Receipt, CalendarCheck } from "lucide-react";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Separator } from "@/components/ui/separator";
import { AddressForm } from "@/features/customers/components/address-form";
import type {
  CustomerDetailDto,
  CustomerBookingHistoryItem,
  CustomerPaymentItem,
} from "@/server/dto/customer.dto";
import { BookingStatus, BOOKING_STATUS_LABELS } from "@/types/booking";
import { formatCurrency } from "@/lib/utils/format";
import { PAYMENT_METHOD_LABELS, PaymentMethod } from "@/types/payment";

interface CustomerDetailViewProps {
  customer: CustomerDetailDto;
  bookings: CustomerBookingHistoryItem[];
  payments: CustomerPaymentItem[];
  basePath?: string;
  canEdit?: boolean;
}

export function CustomerDetailView({
  customer,
  bookings,
  payments,
  basePath = "/admin/customers",
  canEdit = true,
}: CustomerDetailViewProps) {
  return (
    <div className="space-y-6">
      <div className="flex items-center gap-4">
        <Button variant="ghost" size="icon" asChild>
          <Link href={basePath}>
            <ArrowLeft className="size-4" />
          </Link>
        </Button>
        <div>
          <h2 className="text-2xl font-bold tracking-tight">
            {customer.firstName} {customer.lastName}
          </h2>
          <p className="text-muted-foreground">{customer.email}</p>
        </div>
        <Badge
          className="ml-auto"
          variant={customer.isActive ? "success" : "secondary"}
        >
          {customer.isActive ? "Active" : "Inactive"}
        </Badge>
      </div>

      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-sm text-muted-foreground">Phone</CardTitle>
          </CardHeader>
          <CardContent>{customer.phone ?? "—"}</CardContent>
        </Card>
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-sm text-muted-foreground">GST</CardTitle>
          </CardHeader>
          <CardContent>{customer.gstNumber ?? "—"}</CardContent>
        </Card>
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-sm text-muted-foreground">Bookings</CardTitle>
          </CardHeader>
          <CardContent className="text-2xl font-bold">
            {customer.bookingsCount}
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-sm text-muted-foreground">Total Spent</CardTitle>
          </CardHeader>
          <CardContent className="text-2xl font-bold">
            {formatCurrency(customer.totalSpent)}
          </CardContent>
        </Card>
      </div>

      {customer.notes && (
        <Card>
          <CardHeader>
            <CardTitle className="text-base">Notes</CardTitle>
          </CardHeader>
          <CardContent className="text-sm text-muted-foreground">
            {customer.notes}
          </CardContent>
        </Card>
      )}

      <div className="grid gap-6 lg:grid-cols-2">
        <Card>
          <CardHeader>
            <CardTitle className="flex items-center gap-2 text-base">
              <MapPin className="size-4" />
              Addresses ({customer.addresses.length})
            </CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            {customer.addresses.length === 0 ? (
              <p className="text-sm text-muted-foreground">No addresses yet</p>
            ) : (
              customer.addresses.map((address) => (
                <div key={address.id} className="rounded-lg border p-3 text-sm">
                  <div className="mb-1 flex items-center gap-2">
                    <span className="font-medium">{address.label}</span>
                    {address.isDefault && (
                      <Badge variant="secondary" className="text-xs">
                        Default
                      </Badge>
                    )}
                  </div>
                  <p>{address.line1}</p>
                  {address.line2 && <p>{address.line2}</p>}
                  <p>
                    {address.city}, {address.state} {address.pincode}
                  </p>
                </div>
              ))
            )}

            {canEdit && (
              <>
                <Separator />
                <AddressForm customerId={customer.id} />
              </>
            )}
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle className="flex items-center gap-2 text-base">
              <CalendarCheck className="size-4" />
              Booking History
            </CardTitle>
          </CardHeader>
          <CardContent>
            {bookings.length === 0 ? (
              <p className="text-sm text-muted-foreground">No bookings yet</p>
            ) : (
              <div className="space-y-3">
                {bookings.map((booking) => (
                  <div
                    key={booking.id}
                    className="flex items-start justify-between rounded-lg border p-3 text-sm"
                  >
                    <div>
                      <p className="font-medium">{booking.bookingNumber}</p>
                      <p className="text-muted-foreground">{booking.serviceName}</p>
                      <p className="text-xs text-muted-foreground">
                        {booking.scheduledDate}
                      </p>
                    </div>
                    <div className="text-right">
                      <Badge variant="outline">
                        {BOOKING_STATUS_LABELS[booking.status as BookingStatus] ??
                          booking.status}
                      </Badge>
                      <p className="mt-1 font-medium">
                        {formatCurrency(booking.totalAmount)}
                      </p>
                    </div>
                  </div>
                ))}
              </div>
            )}
          </CardContent>
        </Card>
      </div>

      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2 text-base">
            <Receipt className="size-4" />
            Payments
          </CardTitle>
        </CardHeader>
        <CardContent>
          {payments.length === 0 ? (
            <p className="text-sm text-muted-foreground">No payments yet</p>
          ) : (
            <div className="overflow-x-auto">
              <table className="w-full text-sm">
                <thead>
                  <tr className="border-b text-left text-muted-foreground">
                    <th className="pb-2 font-medium">Booking</th>
                    <th className="pb-2 font-medium">Method</th>
                    <th className="pb-2 font-medium">Status</th>
                    <th className="pb-2 font-medium">Amount</th>
                  </tr>
                </thead>
                <tbody>
                  {payments.map((payment) => (
                    <tr key={payment.id} className="border-b last:border-0">
                      <td className="py-2">{payment.bookingNumber}</td>
                      <td className="py-2">
                        {PAYMENT_METHOD_LABELS[payment.method as PaymentMethod] ??
                          payment.method}
                      </td>
                      <td className="py-2">
                        <Badge variant="outline">{payment.status}</Badge>
                      </td>
                      <td className="py-2 font-medium">
                        {formatCurrency(payment.amount)}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </CardContent>
      </Card>
    </div>
  );
}
