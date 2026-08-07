import { CustomersPage } from "@/features/customers/components/customers-page";

export default function AdminCustomersPage({
  searchParams,
}: {
  searchParams: Promise<Record<string, string | string[] | undefined>>;
}) {
  return (
    <CustomersPage
      searchParams={searchParams}
      basePath="/admin/customers"
      canCreate
    />
  );
}
