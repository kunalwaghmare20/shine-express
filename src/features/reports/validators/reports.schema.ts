import { z } from "zod";

export const reportPeriodSchema = z.enum([
  "daily",
  "weekly",
  "monthly",
  "yearly",
]);

export const reportsQuerySchema = z.object({
  period: reportPeriodSchema.default("monthly"),
  branchId: z.string().optional(),
  date: z.string().optional(), // anchor date YYYY-MM-DD, defaults to today
});

export type ReportPeriod = z.infer<typeof reportPeriodSchema>;
export type ReportsQuery = z.infer<typeof reportsQuerySchema>;
