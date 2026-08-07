import { NextRequest } from "next/server";
import {
  requireEmployeeReadAccess,
  assertBranchAccess,
} from "@/lib/auth";
import { apiSuccess, handleApiError } from "@/lib/api";
import { getPaginationMeta, parsePaginationParams } from "@/lib/api/pagination";
import {
  getEmployeeAttendance,
  getEmployeeById,
} from "@/server/services/employee.service";

type RouteParams = { params: Promise<{ id: string }> };

export async function GET(request: NextRequest, { params }: RouteParams) {
  try {
    const ctx = await requireEmployeeReadAccess();
    const { id } = await params;
    const employee = await getEmployeeById(id);
    assertBranchAccess(ctx, employee.branchId);

    const { page, limit } = parsePaginationParams(request.nextUrl.searchParams);
    const result = await getEmployeeAttendance(id, page, limit);

    return apiSuccess(
      result.items,
      200,
      getPaginationMeta(result.total, result.page, result.limit)
    );
  } catch (error) {
    return handleApiError(error);
  }
}
