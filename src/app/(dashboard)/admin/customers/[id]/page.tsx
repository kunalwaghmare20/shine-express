import { notFound } from "next/navigation";
import { CustomerDetailView } from "@/features/customers/components/customer-detail-view";
import {
  getCustomerBookings,
  getCustomerById,
  getCustomerPayments,
} from "@/server/services/customer.service";
import { CustomerServiceError } from "@/server/services/customer.service";

export default async function AdminCustomerDetailPage({
  params,
}: {
  params: Promise<{ id: string }>;
}) {
  const { id } = await params;

  try {
    const [customer, bookings, payments] = await Promise.all([
      getCustomerById(id),
      getCustomerBookings(id, 1, 10),
      getCustomerPayments(id, 1, 10),
    ]);

    return (
      <CustomerDetailView
        customer={customer}
        bookings={bookings.items}
        payments={payments.items}
        canEdit
      />
    );
  } catch (error) {
    if (error instanceof CustomerServiceError && error.statusCode === 404) {
      notFound();
    }
    throw error;
  }
}
