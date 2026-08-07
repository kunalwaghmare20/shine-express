import { NextRequest } from "next/server";
import { requireAuth } from "@/lib/auth";
import { apiSuccess, handleApiError } from "@/lib/api";
import { getPaginationMeta } from "@/lib/api/pagination";
import {
  getUnreadCount,
  listNotifications,
} from "@/server/services/notifications/notification.service";

export async function GET(request: NextRequest) {
  try {
    const user = await requireAuth();
    const params = request.nextUrl.searchParams;
    const page = Number(params.get("page") ?? "1");
    const limit = Number(params.get("limit") ?? "20");
    const unreadOnly = params.get("unreadOnly") === "true";

    if (params.get("countOnly") === "true") {
      const unreadCount = await getUnreadCount(user.id);
      return apiSuccess({ unreadCount });
    }

    const result = await listNotifications(user.id, page, limit, unreadOnly);
    return apiSuccess(
      result.items,
      200,
      getPaginationMeta(result.total, result.page, result.limit)
    );
  } catch (error) {
    return handleApiError(error);
  }
}
