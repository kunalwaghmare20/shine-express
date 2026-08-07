"use client";

import {
  Bar,
  BarChart,
  CartesianGrid,
  Line,
  LineChart,
  ResponsiveContainer,
  Tooltip,
  XAxis,
  YAxis,
} from "recharts";
import { useRouter, useSearchParams } from "next/navigation";
import { useTransition } from "react";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import type { ReportsData } from "@/server/dto/reports.dto";
import type { ReportPeriod } from "@/features/reports/validators/reports.schema";
import { BOOKING_STATUS_LABELS, BookingStatus } from "@/types/booking";
import { formatCurrency, formatNumber } from "@/lib/utils/format";

const PERIODS: { value: ReportPeriod; label: string }[] = [
  { value: "daily", label: "Daily" },
  { value: "weekly", label: "Weekly" },
  { value: "monthly", label: "Monthly" },
  { value: "yearly", label: "Yearly" },
];

interface ReportsDashboardProps {
  data: ReportsData;
  basePath?: string;
}

export function ReportsDashboard({
  data,
  basePath = "/admin/reports",
}: ReportsDashboardProps) {
  const router = useRouter();
  const searchParams = useSearchParams();
  const [isPending, startTransition] = useTransition();
  const period = (searchParams.get("period") as ReportPeriod) || "monthly";

  function setPeriod(next: ReportPeriod) {
    const params = new URLSearchParams(searchParams.toString());
    params.set("period", next);
    startTransition(() => {
      router.push(`${basePath}?${params.toString()}`);
    });
  }

  return (
    <div className="space-y-6">
      <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <p className="text-sm text-muted-foreground">{data.range.label}</p>
          {isPending && (
            <p className="text-xs text-muted-foreground">Updating…</p>
          )}
        </div>
        <div className="flex flex-wrap gap-2">
          {PERIODS.map((p) => (
            <button
              key={p.value}
              type="button"
              onClick={() => setPeriod(p.value)}
              className={`rounded-md border px-3 py-1.5 text-sm transition-colors ${
                period === p.value
                  ? "border-primary bg-primary text-primary-foreground"
                  : "hover:bg-accent"
              }`}
            >
              {p.label}
            </button>
          ))}
        </div>
      </div>

      <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-6">
        {[
          {
            label: "Total Bookings",
            value: formatNumber(data.summary.totalBookings),
          },
          {
            label: "Completed",
            value: formatNumber(data.summary.completedBookings),
          },
          {
            label: "Cancelled",
            value: formatNumber(data.summary.cancelledBookings),
          },
          {
            label: "Revenue (Cash)",
            value: formatCurrency(data.summary.revenue),
          },
          {
            label: "Avg Ticket",
            value: formatCurrency(data.summary.averageTicket),
          },
          {
            label: "New Customers",
            value: formatNumber(data.summary.newCustomers),
          },
        ].map((card) => (
          <Card key={card.label}>
            <CardHeader className="pb-2">
              <CardTitle className="text-sm font-medium text-muted-foreground">
                {card.label}
              </CardTitle>
            </CardHeader>
            <CardContent>
              <p className="text-2xl font-bold">{card.value}</p>
            </CardContent>
          </Card>
        ))}
      </div>

      <div className="grid gap-4 lg:grid-cols-2">
        <Card>
          <CardHeader>
            <CardTitle>Revenue Trend</CardTitle>
          </CardHeader>
          <CardContent>
            {data.revenueTrend.length === 0 ? (
              <EmptyChart />
            ) : (
              <ResponsiveContainer width="100%" height={280}>
                <BarChart data={data.revenueTrend}>
                  <CartesianGrid strokeDasharray="3 3" className="stroke-border" />
                  <XAxis dataKey="label" tick={{ fontSize: 11 }} />
                  <YAxis
                    tick={{ fontSize: 11 }}
                    tickFormatter={(v) => `₹${(v / 1000).toFixed(0)}k`}
                  />
                  <Tooltip
                    formatter={(value: number) => [
                      formatCurrency(value),
                      "Revenue",
                    ]}
                  />
                  <Bar dataKey="revenue" fill="var(--primary)" radius={[4, 4, 0, 0]} />
                </BarChart>
              </ResponsiveContainer>
            )}
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Customer Growth</CardTitle>
          </CardHeader>
          <CardContent>
            {data.customerGrowth.length === 0 ? (
              <EmptyChart />
            ) : (
              <ResponsiveContainer width="100%" height={280}>
                <LineChart data={data.customerGrowth}>
                  <CartesianGrid strokeDasharray="3 3" className="stroke-border" />
                  <XAxis dataKey="label" tick={{ fontSize: 11 }} />
                  <YAxis allowDecimals={false} tick={{ fontSize: 11 }} />
                  <Tooltip
                    formatter={(value: number) => [value, "New customers"]}
                  />
                  <Line
                    type="monotone"
                    dataKey="newCustomers"
                    stroke="var(--primary)"
                    strokeWidth={2}
                    dot={{ r: 3 }}
                  />
                </LineChart>
              </ResponsiveContainer>
            )}
          </CardContent>
        </Card>
      </div>

      <div className="grid gap-4 lg:grid-cols-2">
        <Card>
          <CardHeader>
            <CardTitle>Service Popularity</CardTitle>
          </CardHeader>
          <CardContent>
            {data.servicePopularity.length === 0 ? (
              <p className="py-8 text-center text-sm text-muted-foreground">
                No booking data in this period
              </p>
            ) : (
              <div className="space-y-3">
                {data.servicePopularity.map((row, index) => (
                  <div
                    key={row.serviceId}
                    className="flex items-center justify-between rounded-lg border p-3 text-sm"
                  >
                    <div>
                      <p className="font-medium">
                        #{index + 1} {row.serviceName}
                      </p>
                      <p className="text-xs text-muted-foreground">
                        {row.bookings} bookings
                      </p>
                    </div>
                    <p className="font-medium">{formatCurrency(row.revenue)}</p>
                  </div>
                ))}
              </div>
            )}
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Employee Performance</CardTitle>
          </CardHeader>
          <CardContent>
            {data.employeePerformance.length === 0 ? (
              <p className="py-8 text-center text-sm text-muted-foreground">
                No staff assignments in this period
              </p>
            ) : (
              <div className="overflow-x-auto">
                <table className="w-full text-sm">
                  <thead>
                    <tr className="border-b text-left text-muted-foreground">
                      <th className="pb-2 font-medium">Employee</th>
                      <th className="pb-2 font-medium">Assigned</th>
                      <th className="pb-2 font-medium">Completed</th>
                      <th className="pb-2 font-medium">Rate</th>
                    </tr>
                  </thead>
                  <tbody>
                    {data.employeePerformance.map((row) => (
                      <tr key={row.employeeId} className="border-b last:border-0">
                        <td className="py-2">
                          <p className="font-medium">{row.employeeName}</p>
                          <p className="text-xs text-muted-foreground">
                            {row.employeeCode}
                          </p>
                        </td>
                        <td className="py-2">{row.jobsAssigned}</td>
                        <td className="py-2">{row.jobsCompleted}</td>
                        <td className="py-2">
                          <Badge variant="outline">{row.completionRate}%</Badge>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            )}
          </CardContent>
        </Card>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Booking Status Breakdown</CardTitle>
        </CardHeader>
        <CardContent>
          {data.statusBreakdown.length === 0 ? (
            <p className="py-6 text-center text-sm text-muted-foreground">
              No bookings in this period
            </p>
          ) : (
            <div className="flex flex-wrap gap-3">
              {data.statusBreakdown.map((row) => (
                <div
                  key={row.status}
                  className="rounded-lg border px-4 py-3 text-sm"
                >
                  <p className="text-muted-foreground">
                    {BOOKING_STATUS_LABELS[row.status as BookingStatus] ??
                      row.status}
                  </p>
                  <p className="text-xl font-bold">{row.count}</p>
                </div>
              ))}
            </div>
          )}
        </CardContent>
      </Card>
    </div>
  );
}

function EmptyChart() {
  return (
    <div className="flex h-[280px] items-center justify-center text-sm text-muted-foreground">
      No data for this period
    </div>
  );
}
