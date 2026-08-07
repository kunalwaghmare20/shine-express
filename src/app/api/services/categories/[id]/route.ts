import { NextRequest } from "next/server";
import { requireServiceWriteAccess } from "@/lib/auth";
import { apiSuccess, handleApiError } from "@/lib/api";
import { updateCategorySchema } from "@/features/services/validators/service.schema";
import {
  deleteCategory,
  updateCategory,
} from "@/server/services/service.service";

type RouteParams = { params: Promise<{ id: string }> };

export async function PATCH(request: NextRequest, { params }: RouteParams) {
  try {
    await requireServiceWriteAccess();
    const { id } = await params;
    const body = await request.json();
    const input = updateCategorySchema.parse(body);
    const category = await updateCategory(id, input);
    return apiSuccess(category);
  } catch (error) {
    return handleApiError(error);
  }
}

export async function DELETE(_request: NextRequest, { params }: RouteParams) {
  try {
    await requireServiceWriteAccess();
    const { id } = await params;
    await deleteCategory(id);
    return apiSuccess({ deleted: true });
  } catch (error) {
    return handleApiError(error);
  }
}
