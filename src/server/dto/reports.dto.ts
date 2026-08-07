export type ReportPeriod = "daily" | "weekly" | "monthly" | "yearly";

export interface ReportRange {
  period: ReportPeriod;
  label: string;
  start: string;
  end: string;
}

export interface ReportSummary {
  totalBookings: number;
  completedBookings: number;
  cancelledBookings: number;
  revenue: number;
  newCustomers: number;
  averageTicket: number;
}

export interface RevenuePoint {
  key: string;
  label: string;
  revenue: number;
  bookings: number;
}

export interface ServicePopularityPoint {
  serviceId: string;
  serviceName: string;
  bookings: number;
  revenue: number;
}

export interface EmployeePerformancePoint {
  employeeId: string;
  employeeName: string;
  employeeCode: string;
  jobsAssigned: number;
  jobsCompleted: number;
  completionRate: number;
}

export interface CustomerGrowthPoint {
  key: string;
  label: string;
  newCustomers: number;
}

export interface BookingStatusBreakdown {
  status: string;
  count: number;
}

export interface ReportsData {
  range: ReportRange;
  summary: ReportSummary;
  revenueTrend: RevenuePoint[];
  servicePopularity: ServicePopularityPoint[];
  employeePerformance: EmployeePerformancePoint[];
  customerGrowth: CustomerGrowthPoint[];
  statusBreakdown: BookingStatusBreakdown[];
}
