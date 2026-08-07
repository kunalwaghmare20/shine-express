import { requireEmployeeReadAccess } from "@/lib/auth";
import { EmployeesPage } from "@/features/employees/components/employees-page";

export default async function BranchManagerStaffPage({
  searchParams,
}: {
  searchParams: Promise<Record<string, string | string[] | undefined>>;
}) {
  const ctx = await requireEmployeeReadAccess();

  return (
    <EmployeesPage
      searchParams={searchParams}
      basePath="/branch-manager/staff"
      canCreate={ctx.canWrite}
      branchScope={ctx.branchScope}
    />
  );
}
