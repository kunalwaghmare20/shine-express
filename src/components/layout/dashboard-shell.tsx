"use client";

import {
  BarChart3,
  Bell,
  Briefcase,
  Building2,
  CalendarCheck,
  Clock,
  CreditCard,
  FileText,
  History,
  IndianRupee,
  LayoutDashboard,
  PlusCircle,
  Settings,
  Sparkles,
  User,
  UserCog,
  Users,
} from "lucide-react";
import { usePathname } from "next/navigation";
import { NAVIGATION } from "@/config/navigation";
import { UserRole } from "@/config/roles";
import {
  Sidebar,
  SidebarContent,
  SidebarFooter,
  SidebarHeader,
  SidebarNavItem,
  SidebarProvider,
} from "@/components/layout/sidebar";
import { Navbar } from "@/components/layout/navbar";
import { siteConfig } from "@/config/site";

const ICON_MAP = {
  LayoutDashboard,
  CalendarCheck,
  Users,
  UserCog,
  Sparkles,
  Building2,
  IndianRupee,
  CreditCard,
  FileText,
  BarChart3,
  Settings,
  Briefcase,
  Clock,
  User,
  PlusCircle,
  History,
  Bell,
} as const;

interface DashboardShellProps {
  role: UserRole;
  title?: string;
  children: React.ReactNode;
}

export function DashboardShell({ role, title, children }: DashboardShellProps) {
  const pathname = usePathname();
  const navItems = NAVIGATION[role] ?? [];

  return (
    <SidebarProvider>
      <div className="flex min-h-screen w-full">
        <Sidebar>
          <SidebarHeader>
            <div className="flex items-center gap-2">
              <div className="flex size-8 items-center justify-center rounded-lg bg-primary text-primary-foreground">
                <Sparkles className="size-4" />
              </div>
              <div className="flex flex-col">
                <span className="text-sm font-semibold leading-none">
                  {siteConfig.name}
                </span>
                <span className="text-xs text-muted-foreground capitalize">
                  {role.replace("_", " ").toLowerCase()}
                </span>
              </div>
            </div>
          </SidebarHeader>

          <SidebarContent>
            {navItems.map((item) => {
              const Icon =
                ICON_MAP[item.icon as keyof typeof ICON_MAP] ?? LayoutDashboard;
              const isActive =
                pathname === item.href ||
                (item.href !== "/" && pathname.startsWith(item.href));

              return (
                <SidebarNavItem
                  key={item.href}
                  href={item.href}
                  icon={Icon}
                  label={item.title}
                  active={isActive}
                />
              );
            })}
          </SidebarContent>

          <SidebarFooter>
            <p className="px-3 text-xs text-muted-foreground">
              © {new Date().getFullYear()} Shine Express
            </p>
          </SidebarFooter>
        </Sidebar>

        <div className="flex min-h-screen flex-1 flex-col">
          <Navbar title={title} />
          <main className="flex-1 overflow-y-auto p-4 md:p-6">{children}</main>
        </div>
      </div>
    </SidebarProvider>
  );
}
