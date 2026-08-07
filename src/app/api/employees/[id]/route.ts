import { NextRequest } from "next/server";
import {
  requireEmployeeReadAccess,
  requireEmployeeWriteAccess,
  assertBranchAccess,
} from "@/lib/auth";
import { apiSuccess, handleApiError } from "@/lib/api";
import { updateEmployeeSchema } from "@/features/employees/validators/employee.schema";
import {
  deleteEmployee,
  getEmployeeById,
  updateEmployee,
} from "@/server/services/employee.service";

type RouteParams = { params: Promise<{ id: string }> };

export async function GET(_request: NextRequest, { params }: RouteParams) {
  try {
    const ctx = await requireEmployeeReadAccess();
    const { id } = await params;
    const employee = await getEmployeeById(id);
    assertBranchAccess(ctx, employee.branchId);
    return apiSuccess(employee);
  } catch (error) {
    return handleApiError(error);
  }
}

export async function PATCH(request: NextRequest, { params }: RouteParams) {
  try {
    const { id } = await params;
    const existing = await getEmployeeById(id);
    const ctx = await requireEmployeeWriteAccess(existing.branchId);
    assertBranchAccess(ctx, existing.branchId);

    const body = await request.json();
    const input = updateEmployeeSchema.parse(body);

    if (input.branchId) {
      assertBranchAccess(ctx, input.branchId);
    }

    const employee = await updateEmployee(id, input);
    return apiSuccess(employee);
  } catch (error) {
    return handleApiError(error);
  }
}

export async function DELETE(_request: NextRequest, { params }: RouteParams) {
  try {
    const { id } = await params;
    const existing = await getEmployeeById(id);
    const ctx = await requireEmployeeWriteAccess(existing.branchId);
    assertBranchAccess(ctx, existing.branchId);

    await deleteEmployee(id);
    return apiSuccess({ deleted: true });
  } catch (error) {
    return handleApiError(error);
  }
}
