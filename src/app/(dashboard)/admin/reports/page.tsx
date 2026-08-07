import { requireBookingManageAccess } from "@/lib/auth";
import { ReportsPage } from "@/features/reports/components/reports-page";

export default async function AdminReportsPage({
  searchParams,
}: {
  searchParams: Promise<Record<string, string | string[] | undefined>>;
}) {
  await requireBookingManageAccess();

  return (
    <ReportsPage searchParams={searchParams} basePath="/admin/reports" />
  );
}
