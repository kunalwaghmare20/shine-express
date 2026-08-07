import { requireBookingManageAccess } from "@/lib/auth";
import { ReportsPage } from "@/features/reports/components/reports-page";

export default async function BranchManagerReportsPage({
  searchParams,
}: {
  searchParams: Promise<Record<string, string | string[] | undefined>>;
}) {
  const access = await requireBookingManageAccess();

  return (
    <ReportsPage
      searchParams={searchParams}
      basePath="/branch-manager/reports"
      branchScope={access.branchScope}
    />
  );
}
