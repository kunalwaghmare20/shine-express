import { UserRole } from "@/config/roles";
import { requireRole } from "@/lib/auth";
import { DashboardShell } from "@/components/layout/dashboard-shell";

export const dynamic = "force-dynamic";

export default async function CustomerLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  await requireRole(UserRole.CUSTOMER);

  return (
    <DashboardShell role={UserRole.CUSTOMER} title="Customer Portal">
      {children}
    </DashboardShell>
  );
}
