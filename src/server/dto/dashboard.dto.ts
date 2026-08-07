export interface DashboardStatCard {
  key: string;
  label: string;
  value: number;
  formattedValue: string;
  change?: number;
  changeLabel?: string;
}

export interface MonthlyRevenuePoint {
  month: string;
  label: string;
  revenue: number;
}

export interface BookingTrendPoint {
  date: string;
  label: string;
  bookings: number;
}

export interface TopServicePoint {
  serviceId: string;
  serviceName: string;
  bookings: number;
}

export interface RecentBookingRow {
  id: string;
  bookingNumber: string;
  customerName: string;
  serviceName: string;
  status: string;
  scheduledDate: string;
  totalAmount: number;
}

export interface AdminDashboardData {
  stats: DashboardStatCard[];
  monthlyRevenue: MonthlyRevenuePoint[];
  bookingTrends: BookingTrendPoint[];
  topServices: TopServicePoint[];
  recentBookings: RecentBookingRow[];
}
