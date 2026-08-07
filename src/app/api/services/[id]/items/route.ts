import { NextRequest } from "next/server";
import {
  requireServiceReadAccess,
  requireServiceWriteAccess,
} from "@/lib/auth";
import { apiSuccess, handleApiError } from "@/lib/api";
import { createServiceItemSchema } from "@/features/services/validators/service.schema";
import {
  addServiceItem,
  getServiceById,
} from "@/server/services/service.service";

type RouteParams = { params: Promise<{ id: string }> };

export async function GET(_request: NextRequest, { params }: RouteParams) {
  try {
    await requireServiceReadAccess();
    const { id } = await params;
    const service = await getServiceById(id);
    return apiSuccess(service.items);
  } catch (error) {
    return handleApiError(error);
  }
}

export async function POST(request: NextRequest, { params }: RouteParams) {
  try {
    await requireServiceWriteAccess();
    const { id } = await params;
    const body = await request.json();
    const input = createServiceItemSchema.parse(body);
    const item = await addServiceItem(id, input);
    return apiSuccess(item, 201);
  } catch (error) {
    return handleApiError(error);
  }
}
