import { CustomersPage } from "@/features/customers/components/customers-page";

export default function BranchManagerCustomersPage({
  searchParams,
}: {
  searchParams: Promise<Record<string, string | string[] | undefined>>;
}) {
  return (
    <CustomersPage
      searchParams={searchParams}
      basePath="/branch-manager/customers"
      canCreate={false}
    />
  );
}
