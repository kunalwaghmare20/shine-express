import {
  CalendarCheck,
  CheckCircle2,
  Clock,
  IndianRupee,
  UserCog,
  Users,
  type LucideIcon,
} from "lucide-react";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import type { DashboardStatCard } from "@/server/dto/dashboard.dto";
import { cn } from "@/lib/utils";

const ICONS: Record<string, LucideIcon> = {
  todayBookings: CalendarCheck,
  pendingJobs: Clock,
  completedToday: CheckCircle2,
  revenueMonth: IndianRupee,
  customers: Users,
  employees: UserCog,
};

interface StatCardProps {
  stat: DashboardStatCard;
  className?: string;
}

export function StatCard({ stat, className }: StatCardProps) {
  const Icon = ICONS[stat.key] ?? CalendarCheck;

  return (
    <Card className={cn("", className)}>
      <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
        <CardTitle className="text-sm font-medium text-muted-foreground">
          {stat.label}
        </CardTitle>
        <Icon className="size-4 text-muted-foreground" />
      </CardHeader>
      <CardContent>
        <div className="text-2xl font-bold">{stat.formattedValue}</div>
      </CardContent>
    </Card>
  );
}

interface StatCardsGridProps {
  stats: DashboardStatCard[];
}

export function StatCardsGrid({ stats }: StatCardsGridProps) {
  return (
    <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-6">
      {stats.map((stat) => (
        <StatCard key={stat.key} stat={stat} />
      ))}
    </div>
  );
}
