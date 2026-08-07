import { requireEmployeeReadAccess } from "@/lib/auth";
import { apiSuccess, handleApiError } from "@/lib/api";
import { listBranches } from "@/server/services/employee.service";

export async function GET() {
  try {
    await requireEmployeeReadAccess();
    const branches = await listBranches();
    return apiSuccess(branches);
  } catch (error) {
    return handleApiError(error);
  }
}
