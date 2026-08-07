import { UserRole } from "@/config/roles";
import { requireRole } from "@/lib/auth";
import { apiSuccess, handleApiError } from "@/lib/api";
import { getAdminDashboardData } from "@/server/services/dashboard.service";

export async function GET() {
  try {
    await requireRole(UserRole.SUPER_ADMIN);
    const data = await getAdminDashboardData();
    return apiSuccess(data);
  } catch (error) {
    return handleApiError(error);
  }
}
