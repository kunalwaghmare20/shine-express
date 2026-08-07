import { NextRequest } from "next/server";
import { requireServiceWriteAccess } from "@/lib/auth";
import { apiSuccess, handleApiError } from "@/lib/api";
import { updateServiceItemSchema } from "@/features/services/validators/service.schema";
import {
  deleteServiceItem,
  updateServiceItem,
} from "@/server/services/service.service";

type RouteParams = { params: Promise<{ id: string; itemId: string }> };

export async function PATCH(request: NextRequest, { params }: RouteParams) {
  try {
    await requireServiceWriteAccess();
    const { id, itemId } = await params;
    const body = await request.json();
    const input = updateServiceItemSchema.parse(body);
    const item = await updateServiceItem(id, itemId, input);
    return apiSuccess(item);
  } catch (error) {
    return handleApiError(error);
  }
}

export async function DELETE(_request: NextRequest, { params }: RouteParams) {
  try {
    await requireServiceWriteAccess();
    const { id, itemId } = await params;
    await deleteServiceItem(id, itemId);
    return apiSuccess({ deleted: true });
  } catch (error) {
    return handleApiError(error);
  }
}
