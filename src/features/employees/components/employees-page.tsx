import { Suspense } from "react";
import { employeeListQuerySchema } from "@/features/employees/validators/employee.schema";
import { EmployeesTable } from "@/features/employees/components/employees-table";
import {
  listBranches,
  listEmployees,
} from "@/server/services/employee.service";
import { Skeleton } from "@/components/ui/skeleton";

interface EmployeesPageProps {
  searchParams: Promise<Record<string, string | string[] | undefined>>;
  basePath?: string;
  canCreate?: boolean;
  branchScope?: string;
}

async function EmployeesContent({
  searchParams,
  basePath,
  canCreate,
  branchScope,
}: EmployeesPageProps) {
  const params = await searchParams;
  const query = employeeListQuerySchema.parse({
    page: params.page,
    limit: params.limit,
    search: params.search,
    sort: params.sort,
    order: params.order,
  });

  const [data, branches] = await Promise.all([
    listEmployees(query, branchScope),
    listBranches(),
  ]);

  const filteredBranches = branchScope
    ? branches.filter((b) => b.id === branchScope)
    : branches;

  return (
    <EmployeesTable
      data={data}
      branches={filteredBranches}
      basePath={basePath}
      canCreate={canCreate}
      defaultBranchId={branchScope}
    />
  );
}

function EmployeesSkeleton() {
  return (
    <div className="space-y-4">
      <Skeleton className="h-10 w-full max-w-sm" />
      <Skeleton className="h-64 w-full" />
    </div>
  );
}

export function EmployeesPage(props: EmployeesPageProps) {
  return (
    <div className="space-y-6">
      <div>
        <h2 className="text-2xl font-bold tracking-tight">Employees</h2>
        <p className="text-muted-foreground">
          Manage staff profiles, skills, documents, and branch assignments.
        </p>
      </div>

      <Suspense fallback={<EmployeesSkeleton />}>
        <EmployeesContent {...props} />
      </Suspense>
    </div>
  );
}
