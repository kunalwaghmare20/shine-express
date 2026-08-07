import { NextRequest } from "next/server";
import {
  requireServiceReadAccess,
  requireServiceWriteAccess,
} from "@/lib/auth";
import { apiSuccess, handleApiError } from "@/lib/api";
import { getPaginationMeta } from "@/lib/api/pagination";
import {
  createServiceSchema,
  serviceListQuerySchema,
} from "@/features/services/validators/service.schema";
import {
  createService,
  listServices,
} from "@/server/services/service.service";

export async function GET(request: NextRequest) {
  try {
    await requireServiceReadAccess();
    const params = Object.fromEntries(request.nextUrl.searchParams);
    const query = serviceListQuerySchema.parse(params);
    const result = await listServices(query);
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
    await requireServiceWriteAccess();
    const body = await request.json();
    const input = createServiceSchema.parse(body);
    const service = await createService(input);
    return apiSuccess(service, 201);
  } catch (error) {
    return handleApiError(error);
  }
}
