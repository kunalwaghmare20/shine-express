import { requireAuth } from "@/lib/auth";
import { apiSuccess, handleApiError } from "@/lib/api";
import { markNotificationRead } from "@/server/services/notifications/notification.service";

export async function PATCH(
  _request: Request,
  { params }: { params: Promise<{ id: string }> }
) {
  try {
    const user = await requireAuth();
    const { id } = await params;
    const notification = await markNotificationRead(user.id, id);
    return apiSuccess(notification);
  } catch (error) {
    return handleApiError(error);
  }
}
