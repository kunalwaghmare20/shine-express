import { NextRequest } from "next/server";
import { requireCustomerReadAccess } from "@/lib/auth";
import { apiSuccess, handleApiError } from "@/lib/api";
import { getPaginationMeta, parsePaginationParams } from "@/lib/api/pagination";
import { getCustomerBookings } from "@/server/services/customer.service";

type RouteParams = { params: Promise<{ id: string }> };

export async function GET(request: NextRequest, { params }: RouteParams) {
  try {
    await requireCustomerReadAccess();
    const { id } = await params;
    const { page, limit } = parsePaginationParams(request.nextUrl.searchParams);
    const result = await getCustomerBookings(id, page, limit);
    return apiSuccess(
      result.items,
      200,
      getPaginationMeta(result.total, result.page, result.limit)
    );
  } catch (error) {
    return handleApiError(error);
  }
}
