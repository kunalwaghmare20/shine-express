import { NextRequest } from "next/server";
import {
  requireEmployeeReadAccess,
  requireEmployeeWriteAccess,
  assertBranchAccess,
} from "@/lib/auth";
import { apiSuccess, handleApiError } from "@/lib/api";
import { createDocumentSchema } from "@/features/employees/validators/employee.schema";
import {
  addEmployeeDocument,
  getEmployeeById,
} from "@/server/services/employee.service";

type RouteParams = { params: Promise<{ id: string }> };

export async function GET(_request: NextRequest, { params }: RouteParams) {
  try {
    const ctx = await requireEmployeeReadAccess();
    const { id } = await params;
    const employee = await getEmployeeById(id);
    assertBranchAccess(ctx, employee.branchId);
    return apiSuccess(employee.documents);
  } catch (error) {
    return handleApiError(error);
  }
}

export async function POST(request: NextRequest, { params }: RouteParams) {
  try {
    const { id } = await params;
    const employee = await getEmployeeById(id);
    const ctx = await requireEmployeeWriteAccess(employee.branchId);
    assertBranchAccess(ctx, employee.branchId);

    const body = await request.json();
    const input = createDocumentSchema.parse(body);
    const document = await addEmployeeDocument(id, input);
    return apiSuccess(document, 201);
  } catch (error) {
    return handleApiError(error);
  }
}
