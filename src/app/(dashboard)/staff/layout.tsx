import { UserRole } from "@/config/roles";
import { requireRole } from "@/lib/auth";
import { DashboardShell } from "@/components/layout/dashboard-shell";

export const dynamic = "force-dynamic";

export default async function StaffLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  await requireRole(UserRole.SERVICE_STAFF);

  return (
    <DashboardShell role={UserRole.SERVICE_STAFF} title="My Jobs">
      {children}
    </DashboardShell>
  );
}
