import { notFound } from "next/navigation";
import {
  requireEmployeeReadAccess,
  assertBranchAccess,
} from "@/lib/auth";
import { EmployeeDetailView } from "@/features/employees/components/employee-detail-view";
import {
  getEmployeeById,
  EmployeeServiceError,
} from "@/server/services/employee.service";

export default async function BranchManagerStaffDetailPage({
  params,
}: {
  params: Promise<{ id: string }>;
}) {
  const { id } = await params;
  const ctx = await requireEmployeeReadAccess();

  try {
    const employee = await getEmployeeById(id);
    assertBranchAccess(ctx, employee.branchId);

    return (
      <EmployeeDetailView
        employee={employee}
        basePath="/branch-manager/staff"
        canEdit={ctx.canWrite}
      />
    );
  } catch (error) {
    if (error instanceof EmployeeServiceError && error.statusCode === 404) {
      notFound();
    }
    throw error;
  }
}
