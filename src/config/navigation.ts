import { UserRole } from "./roles";

export interface NavItem {
  title: string;
  href: string;
  icon?: string; // Lucide icon name — resolved in Sidebar component
  badge?: string;
  children?: NavItem[];
}

/** Sidebar navigation config per role */
export const NAVIGATION: Record<UserRole, NavItem[]> = {
  [UserRole.SUPER_ADMIN]: [
    { title: "Dashboard", href: "/admin", icon: "LayoutDashboard" },
    { title: "Bookings", href: "/admin/bookings", icon: "CalendarCheck" },
    { title: "Customers", href: "/admin/customers", icon: "Users" },
    { title: "Employees", href: "/admin/employees", icon: "UserCog" },
    { title: "Services", href: "/admin/services", icon: "Sparkles" },
    { title: "Branches", href: "/admin/branches", icon: "Building2" },
    { title: "Reports", href: "/admin/reports", icon: "BarChart3" },
    { title: "Notifications", href: "/admin/notifications", icon: "Bell" },
    { title: "Settings", href: "/admin/settings", icon: "Settings" },
  ],

  [UserRole.BRANCH_MANAGER]: [
    { title: "Dashboard", href: "/branch-manager", icon: "LayoutDashboard" },
    {
      title: "Bookings",
      href: "/branch-manager/bookings",
      icon: "CalendarCheck",
    },
    { title: "Customers", href: "/branch-manager/customers", icon: "Users" },
    { title: "Staff", href: "/branch-manager/staff", icon: "UserCog" },
    { title: "Reports", href: "/branch-manager/reports", icon: "BarChart3" },
    {
      title: "Notifications",
      href: "/branch-manager/notifications",
      icon: "Bell",
    },
  ],

  [UserRole.SERVICE_STAFF]: [
    { title: "My Jobs", href: "/staff/jobs", icon: "Briefcase" },
    { title: "Attendance", href: "/staff/attendance", icon: "Clock" },
    { title: "Notifications", href: "/staff/notifications", icon: "Bell" },
    { title: "Profile", href: "/staff/profile", icon: "User" },
  ],

  [UserRole.CUSTOMER]: [
    { title: "Book Service", href: "/book", icon: "PlusCircle" },
    { title: "My Bookings", href: "/bookings", icon: "CalendarCheck" },
    { title: "History", href: "/history", icon: "History" },
    { title: "Notifications", href: "/notifications", icon: "Bell" },
    { title: "Invoices", href: "/invoices", icon: "FileText" },
    { title: "Profile", href: "/profile", icon: "User" },
  ],
};
