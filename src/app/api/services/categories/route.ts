import { NextRequest } from "next/server";
import {
  requireServiceReadAccess,
  requireServiceWriteAccess,
} from "@/lib/auth";
import { apiSuccess, handleApiError } from "@/lib/api";
import { createCategorySchema } from "@/features/services/validators/service.schema";
import {
  createCategory,
  listCategories,
} from "@/server/services/service.service";

export async function GET(request: NextRequest) {
  try {
    await requireServiceReadAccess();
    const activeOnly =
      request.nextUrl.searchParams.get("active") === "true";
    const categories = await listCategories(activeOnly);
    return apiSuccess(categories);
  } catch (error) {
    return handleApiError(error);
  }
}

export async function POST(request: NextRequest) {
  try {
    await requireServiceWriteAccess();
    const body = await request.json();
    const input = createCategorySchema.parse(body);
    const category = await createCategory(input);
    return apiSuccess(category, 201);
  } catch (error) {
    return handleApiError(error);
  }
}
