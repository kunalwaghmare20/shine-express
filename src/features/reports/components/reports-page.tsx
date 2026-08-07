import { Suspense } from "react";
import { reportsQuerySchema } from "@/features/reports/validators/reports.schema";
import { ReportsDashboard } from "@/features/reports/components/reports-dashboard";
import { getReportsData } from "@/server/services/reports.service";
import { Skeleton } from "@/components/ui/skeleton";
import { Card, CardContent, CardHeader } from "@/components/ui/card";

interface ReportsPageProps {
  searchParams: Promise<Record<string, string | string[] | undefined>>;
  basePath?: string;
  branchScope?: string;
}

async function ReportsContent({
  searchParams,
  basePath,
  branchScope,
}: ReportsPageProps) {
  const params = await searchParams;
  const query = reportsQuerySchema.parse({
    period: params.period,
    branchId: params.branchId,
    date: params.date,
  });

  const data = await getReportsData(query, branchScope);

  return <ReportsDashboard data={data} basePath={basePath} />;
}

function ReportsSkeleton() {
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
      <Skeleton className="h-80 w-full" />
    </div>
  );
}

export function ReportsPage(props: ReportsPageProps) {
  return (
    <div className="space-y-6">
      <div>
        <h2 className="text-2xl font-bold tracking-tight">Reports</h2>
        <p className="text-muted-foreground">
          Daily, weekly, monthly, and yearly business insights.
        </p>
      </div>

      <Suspense fallback={<ReportsSkeleton />}>
        <ReportsContent {...props} />
      </Suspense>
    </div>
  );
}
