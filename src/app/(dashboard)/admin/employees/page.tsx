import { EmployeesPage } from "@/features/employees/components/employees-page";

export default function AdminEmployeesPage({
  searchParams,
}: {
  searchParams: Promise<Record<string, string | string[] | undefined>>;
}) {
  return (
    <EmployeesPage
      searchParams={searchParams}
      basePath="/admin/employees"
      canCreate
    />
  );
}
