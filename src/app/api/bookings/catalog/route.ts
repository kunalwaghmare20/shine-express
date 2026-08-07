import { requireBookingAccess } from "@/lib/auth";
import { apiSuccess, handleApiError } from "@/lib/api";
import { listBookingCatalog } from "@/server/services/booking.service";

export async function GET() {
  try {
    await requireBookingAccess();
    const catalog = await listBookingCatalog();
    return apiSuccess(catalog);
  } catch (error) {
    return handleApiError(error);
  }
}
