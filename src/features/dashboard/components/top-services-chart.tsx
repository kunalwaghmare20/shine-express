"use client";

import {
  Bar,
  BarChart,
  CartesianGrid,
  ResponsiveContainer,
  Tooltip,
  XAxis,
  YAxis,
} from "recharts";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import type { TopServicePoint } from "@/server/dto/dashboard.dto";

interface TopServicesChartProps {
  data: TopServicePoint[];
}

export function TopServicesChart({ data }: TopServicesChartProps) {
  const chartData = data.map((item) => ({
    ...item,
    shortName:
      item.serviceName.length > 18
        ? `${item.serviceName.slice(0, 16)}…`
        : item.serviceName,
  }));

  return (
    <Card>
      <CardHeader>
        <CardTitle>Top Services</CardTitle>
      </CardHeader>
      <CardContent>
        {chartData.length === 0 ? (
          <div className="flex h-[280px] items-center justify-center text-sm text-muted-foreground">
            No booking data yet
          </div>
        ) : (
          <ResponsiveContainer width="100%" height={280}>
            <BarChart
              data={chartData}
              layout="vertical"
              margin={{ top: 8, right: 8, left: 8, bottom: 0 }}
            >
              <CartesianGrid strokeDasharray="3 3" className="stroke-border" />
              <XAxis type="number" allowDecimals={false} tick={{ fontSize: 12 }} />
              <YAxis
                type="category"
                dataKey="shortName"
                width={100}
                tick={{ fontSize: 11 }}
                tickLine={false}
                axisLine={false}
              />
              <Tooltip
                formatter={(value: number) => [value, "Bookings"]}
                labelFormatter={(_, payload) =>
                  payload?.[0]?.payload?.serviceName ?? ""
                }
                contentStyle={{
                  borderRadius: "8px",
                  border: "1px solid var(--border)",
                  background: "var(--popover)",
                }}
              />
              <Bar
                dataKey="bookings"
                fill="var(--primary)"
                radius={[0, 4, 4, 0]}
              />
            </BarChart>
          </ResponsiveContainer>
        )}
      </CardContent>
    </Card>
  );
}
