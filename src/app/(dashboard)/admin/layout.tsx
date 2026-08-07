import { UserRole } from "@/config/roles";
import { requireRole } from "@/lib/auth";
import { DashboardShell } from "@/components/layout/dashboard-shell";

export const dynamic = "force-dynamic";

export default async function AdminLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  await requireRole(UserRole.SUPER_ADMIN);

  return (
    <DashboardShell role={UserRole.SUPER_ADMIN} title="Admin">
      {children}
    </DashboardShell>
  );
}
