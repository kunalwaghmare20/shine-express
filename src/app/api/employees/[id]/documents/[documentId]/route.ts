import { NextRequest } from "next/server";
import {
  requireEmployeeWriteAccess,
  assertBranchAccess,
} from "@/lib/auth";
import { apiSuccess, handleApiError } from "@/lib/api";
import {
  deleteEmployeeDocument,
  getEmployeeById,
} from "@/server/services/employee.service";

type RouteParams = { params: Promise<{ id: string; documentId: string }> };

export async function DELETE(_request: NextRequest, { params }: RouteParams) {
  try {
    const { id, documentId } = await params;
    const employee = await getEmployeeById(id);
    const ctx = await requireEmployeeWriteAccess(employee.branchId);
    assertBranchAccess(ctx, employee.branchId);

    await deleteEmployeeDocument(id, documentId);
    return apiSuccess({ deleted: true });
  } catch (error) {
    return handleApiError(error);
  }
}
