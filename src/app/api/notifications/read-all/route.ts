import { requireAuth } from "@/lib/auth";
import { apiSuccess, handleApiError } from "@/lib/api";
import { markAllNotificationsRead } from "@/server/services/notifications/notification.service";

export async function PATCH() {
  try {
    const user = await requireAuth();
    await markAllNotificationsRead(user.id);
    return apiSuccess({ ok: true });
  } catch (error) {
    return handleApiError(error);
  }
}
