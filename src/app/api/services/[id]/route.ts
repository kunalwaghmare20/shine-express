import { NextRequest } from "next/server";
import {
  requireServiceReadAccess,
  requireServiceWriteAccess,
} from "@/lib/auth";
import { apiSuccess, handleApiError } from "@/lib/api";
import { updateServiceSchema } from "@/features/services/validators/service.schema";
import {
  deleteService,
  getServiceById,
  updateService,
} from "@/server/services/service.service";

type RouteParams = { params: Promise<{ id: string }> };

export async function GET(_request: NextRequest, { params }: RouteParams) {
  try {
    await requireServiceReadAccess();
    const { id } = await params;
    const service = await getServiceById(id);
    return apiSuccess(service);
  } catch (error) {
    return handleApiError(error);
  }
}

export async function PATCH(request: NextRequest, { params }: RouteParams) {
  try {
    await requireServiceWriteAccess();
    const { id } = await params;
    const body = await request.json();
    const input = updateServiceSchema.parse(body);
    const service = await updateService(id, input);
    return apiSuccess(service);
  } catch (error) {
    return handleApiError(error);
  }
}

export async function DELETE(_request: NextRequest, { params }: RouteParams) {
  try {
    await requireServiceWriteAccess();
    const { id } = await params;
    await deleteService(id);
    return apiSuccess({ deleted: true });
  } catch (error) {
    return handleApiError(error);
  }
}
