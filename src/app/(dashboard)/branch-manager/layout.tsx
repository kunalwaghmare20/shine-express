import { UserRole } from "@/config/roles";
import { requireRole } from "@/lib/auth";
import { DashboardShell } from "@/components/layout/dashboard-shell";

export const dynamic = "force-dynamic";

export default async function BranchManagerLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  await requireRole(UserRole.BRANCH_MANAGER);

  return (
    <DashboardShell role={UserRole.BRANCH_MANAGER} title="Branch Manager">
      {children}
    </DashboardShell>
  );
}
