"use client";

import {
  CartesianGrid,
  Line,
  LineChart,
  ResponsiveContainer,
  Tooltip,
  XAxis,
  YAxis,
} from "recharts";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import type { BookingTrendPoint } from "@/server/dto/dashboard.dto";

interface BookingTrendsChartProps {
  data: BookingTrendPoint[];
}

export function BookingTrendsChart({ data }: BookingTrendsChartProps) {
  return (
    <Card>
      <CardHeader>
        <CardTitle>Booking Trends (7 days)</CardTitle>
      </CardHeader>
      <CardContent>
        <ResponsiveContainer width="100%" height={280}>
          <LineChart data={data} margin={{ top: 8, right: 8, left: 0, bottom: 0 }}>
            <CartesianGrid strokeDasharray="3 3" className="stroke-border" />
            <XAxis
              dataKey="label"
              tick={{ fontSize: 12 }}
              tickLine={false}
              axisLine={false}
            />
            <YAxis
              allowDecimals={false}
              tick={{ fontSize: 12 }}
              tickLine={false}
              axisLine={false}
            />
            <Tooltip
              formatter={(value: number) => [value, "Bookings"]}
              contentStyle={{
                borderRadius: "8px",
                border: "1px solid var(--border)",
                background: "var(--popover)",
              }}
            />
            <Line
              type="monotone"
              dataKey="bookings"
              stroke="var(--primary)"
              strokeWidth={2}
              dot={{ r: 4 }}
              activeDot={{ r: 6 }}
            />
          </LineChart>
        </ResponsiveContainer>
      </CardContent>
    </Card>
  );
}
