import type { CreateAddressInput, CreateCustomerInput } from "@/features/customers/validators/customer.schema";

export interface CustomerListItem {
  id: string;
  userId: string;
  firstName: string;
  lastName: string;
  email: string;
  phone: string | null;
  gstNumber: string | null;
  isActive: boolean;
  bookingsCount: number;
  totalSpent: number;
  createdAt: string;
}

export interface CustomerAddressDto {
  id: string;
  label: string;
  line1: string;
  line2: string | null;
  city: string;
  state: string;
  pincode: string;
  country: string;
  isDefault: boolean;
}

export interface CustomerDetailDto extends CustomerListItem {
  notes: string | null;
  addresses: CustomerAddressDto[];
  updatedAt: string;
}

export interface CustomerBookingHistoryItem {
  id: string;
  bookingNumber: string;
  serviceName: string;
  status: string;
  scheduledDate: string;
  totalAmount: number;
  createdAt: string;
}

export interface CustomerPaymentItem {
  id: string;
  amount: number;
  method: string;
  status: string;
  paidAt: string | null;
  bookingNumber: string;
}

export type { CreateCustomerInput, CreateAddressInput };
