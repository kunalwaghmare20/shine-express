import { notFound } from "next/navigation";
import { CustomerDetailView } from "@/features/customers/components/customer-detail-view";
import {
  getCustomerBookings,
  getCustomerById,
  getCustomerPayments,
  CustomerServiceError,
} from "@/server/services/customer.service";

export default async function BranchManagerCustomerDetailPage({
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
        basePath="/branch-manager/customers"
        canEdit={false}
      />
    );
  } catch (error) {
    if (error instanceof CustomerServiceError && error.statusCode === 404) {
      notFound();
    }
    throw error;
  }
}
