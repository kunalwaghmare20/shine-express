import { redirect } from "next/navigation";
import { eq, and, isNull } from "drizzle-orm";
import { requireAuth } from "@/lib/auth";
import { getDb } from "@/lib/db";
import { customers } from "@/lib/db/schema";
import { listBookingCatalog } from "@/server/services/booking.service";
import { listBranches } from "@/server/services/employee.service";
import { getCustomerById } from "@/server/services/customer.service";
import { BookServiceWizard } from "@/features/bookings/components/book-service-wizard";
import { UserRole } from "@/config/roles";

export default async function BookServicePage() {
  const user = await requireAuth();

  if (user.role !== UserRole.CUSTOMER) {
    redirect("/admin/bookings");
  }

  const db = getDb();
  const customer = await db.query.customers.findFirst({
    where: and(eq(customers.userId, user.id), isNull(customers.deletedAt)),
  });

  if (!customer) {
    return (
      <div className="space-y-2">
        <h2 className="text-2xl font-bold tracking-tight">Book a Service</h2>
        <p className="text-muted-foreground">
          Your customer profile is not set up yet. Please contact support.
        </p>
      </div>
    );
  }

  const [catalog, detail, branches] = await Promise.all([
    listBookingCatalog(),
    getCustomerById(customer.id),
    listBranches(),
  ]);

  return (
    <div className="space-y-6">
      <div>
        <h2 className="text-2xl font-bold tracking-tight">Book a Service</h2>
        <p className="text-muted-foreground">
          Select a service, date, time, and address to schedule your booking.
        </p>
      </div>
      <BookServiceWizard
        catalog={catalog}
        addresses={detail.addresses}
        branches={branches}
      />
    </div>
  );
}
