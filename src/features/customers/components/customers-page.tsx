import { Suspense } from "react";
import { customerListQuerySchema } from "@/features/customers/validators/customer.schema";
import { CustomersTable } from "@/features/customers/components/customers-table";
import { listCustomers } from "@/server/services/customer.service";
import { Skeleton } from "@/components/ui/skeleton";

interface CustomersPageProps {
  searchParams: Promise<Record<string, string | string[] | undefined>>;
  basePath?: string;
  canCreate?: boolean;
}

async function CustomersContent({
  searchParams,
  basePath,
  canCreate,
}: CustomersPageProps) {
  const params = await searchParams;
  const query = customerListQuerySchema.parse({
    page: params.page,
    limit: params.limit,
    search: params.search,
    sort: params.sort,
    order: params.order,
  });

  const data = await listCustomers(query);

  return (
    <CustomersTable data={data} basePath={basePath} canCreate={canCreate} />
  );
}

function CustomersSkeleton() {
  return (
    <div className="space-y-4">
      <Skeleton className="h-10 w-full max-w-sm" />
      <Skeleton className="h-64 w-full" />
    </div>
  );
}

export function CustomersPage({
  searchParams,
  basePath = "/admin/customers",
  canCreate = true,
}: CustomersPageProps) {
  return (
    <div className="space-y-6">
      <div>
        <h2 className="text-2xl font-bold tracking-tight">Customers</h2>
        <p className="text-muted-foreground">
          Manage customer profiles, addresses, and view booking history.
        </p>
      </div>

      <Suspense fallback={<CustomersSkeleton />}>
        <CustomersContent
          searchParams={searchParams}
          basePath={basePath}
          canCreate={canCreate}
        />
      </Suspense>
    </div>
  );
}
