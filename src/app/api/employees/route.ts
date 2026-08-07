import { NextRequest } from "next/server";
import {
  requireEmployeeReadAccess,
  requireEmployeeWriteAccess,
  assertBranchAccess,
} from "@/lib/auth";
import { apiSuccess, handleApiError } from "@/lib/api";
import { getPaginationMeta } from "@/lib/api/pagination";
import {
  createEmployeeSchema,
  employeeListQuerySchema,
} from "@/features/employees/validators/employee.schema";
import {
  createEmployee,
  listEmployees,
} from "@/server/services/employee.service";

export async function GET(request: NextRequest) {
  try {
    const ctx = await requireEmployeeReadAccess();
    const params = Object.fromEntries(request.nextUrl.searchParams);
    const query = employeeListQuerySchema.parse(params);
    const result = await listEmployees(query, ctx.branchScope);

    return apiSuccess(
      result.items,
      200,
      getPaginationMeta(result.total, result.page, result.limit)
    );
  } catch (error) {
    return handleApiError(error);
  }
}

export async function POST(request: NextRequest) {
  try {
    const body = await request.json();
    const input = createEmployeeSchema.parse(body);
    const ctx = await requireEmployeeWriteAccess(input.branchId);
    assertBranchAccess(ctx, input.branchId);

    const employee = await createEmployee(input);
    return apiSuccess(employee, 201);
  } catch (error) {
    return handleApiError(error);
  }
}
