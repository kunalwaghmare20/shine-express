import { Suspense } from "react";
import { getUserDisplayName, requireRole } from "@/lib/auth";
import { UserRole } from "@/config/roles";
import { getAdminDashboardData } from "@/server/services/dashboard.service";
import { StatCardsGrid } from "@/features/dashboard/components/stat-cards";
import { RevenueChart } from "@/features/dashboard/components/revenue-chart";
import { BookingTrendsChart } from "@/features/dashboard/components/booking-trends-chart";
import { TopServicesChart } from "@/features/dashboard/components/top-services-chart";
import {
  RecentBookingsTable,
  RecentBookingsTableSkeleton,
} from "@/features/dashboard/components/recent-bookings-table";
import { Card, CardContent, CardHeader } from "@/components/ui/card";
import { Skeleton } from "@/components/ui/skeleton";

function DashboardSkeleton() {
  return (
    <div className="space-y-6">
      <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-6">
        {Array.from({ length: 6 }).map((_, i) => (
          <Card key={i}>
            <CardHeader>
              <Skeleton className="h-4 w-24" />
            </CardHeader>
            <CardContent>
              <Skeleton className="h-8 w-16" />
            </CardContent>
          </Card>
        ))}
      </div>
      <div className="grid gap-4 lg:grid-cols-2">
        <Card>
          <CardHeader>
            <Skeleton className="h-6 w-32" />
          </CardHeader>
          <CardContent>
            <Skeleton className="h-[280px] w-full" />
          </CardContent>
        </Card>
        <Card>
          <CardHeader>
            <Skeleton className="h-6 w-32" />
          </CardHeader>
          <CardContent>
            <Skeleton className="h-[280px] w-full" />
          </CardContent>
        </Card>
      </div>
      <RecentBookingsTableSkeleton />
    </div>
  );
}

async function DashboardContent() {
  const user = await requireRole(UserRole.SUPER_ADMIN);
  const data = await getAdminDashboardData();

  return (
    <div className="space-y-6">
      <div>
        <h2 className="text-2xl font-bold tracking-tight">Dashboard</h2>
        <p className="text-muted-foreground">
          Welcome back, {getUserDisplayName(user)}. Here&apos;s your business
          overview.
        </p>
      </div>

      <StatCardsGrid stats={data.stats} />

      <div className="grid gap-4 lg:grid-cols-2">
        <RevenueChart data={data.monthlyRevenue} />
        <BookingTrendsChart data={data.bookingTrends} />
      </div>

      <div className="grid gap-4 lg:grid-cols-2">
        <TopServicesChart data={data.topServices} />
        <Card>
          <CardHeader>
            <h3 className="font-semibold leading-none">Quick Actions</h3>
          </CardHeader>
          <CardContent className="grid gap-2 sm:grid-cols-2">
            {[
              { href: "/admin/bookings", label: "Manage Bookings" },
              { href: "/admin/customers", label: "View Customers" },
              { href: "/admin/employees", label: "Manage Staff" },
              { href: "/admin/services", label: "Edit Services" },
            ].map((action) => (
              <a
                key={action.href}
                href={action.href}
                className="rounded-lg border px-4 py-3 text-sm font-medium transition-colors hover:bg-accent"
              >
                {action.label}
              </a>
            ))}
          </CardContent>
        </Card>
      </div>

      <RecentBookingsTable bookings={data.recentBookings} />
    </div>
  );
}

export function AdminDashboard() {
  return (
    <Suspense fallback={<DashboardSkeleton />}>
      <DashboardContent />
    </Suspense>
  );
}
