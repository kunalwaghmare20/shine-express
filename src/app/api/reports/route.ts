import { NextRequest } from "next/server";
import {
  requireBookingManageAccess,
} from "@/lib/auth";
import { apiSuccess, handleApiError } from "@/lib/api";
import { reportsQuerySchema } from "@/features/reports/validators/reports.schema";
import { getReportsData } from "@/server/services/reports.service";

export async function GET(request: NextRequest) {
  try {
    const ctx = await requireBookingManageAccess();
    const params = Object.fromEntries(request.nextUrl.searchParams);
    const query = reportsQuerySchema.parse(params);
    const data = await getReportsData(query, ctx.branchScope);
    return apiSuccess(data);
  } catch (error) {
    return handleApiError(error);
  }
}
