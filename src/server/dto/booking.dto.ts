export interface BookingListItem {
  id: string;
  bookingNumber: string;
  customerId: string;
  customerName: string;
  customerPhone: string | null;
  serviceId: string;
  serviceName: string;
  branchId: string;
  branchName: string;
  status: string;
  scheduledDate: string;
  scheduledTime: string;
  totalAmount: number;
  assignedStaff: string[];
  createdAt: string;
}

export interface BookingItemDto {
  id: string;
  serviceItemId: string | null;
  name: string;
  description: string | null;
  price: number;
  quantity: number;
}

export interface BookingAssignmentDto {
  id: string;
  employeeId: string;
  employeeName: string;
  employeeCode: string;
  isPrimary: boolean;
  acceptedAt: string | null;
  rejectedAt: string | null;
}

export interface BookingStatusHistoryDto {
  id: string;
  fromStatus: string | null;
  toStatus: string;
  notes: string | null;
  changedByName: string | null;
  createdAt: string;
}

export interface BookingAddressDto {
  id: string;
  label: string;
  line1: string;
  line2: string | null;
  city: string;
  state: string;
  pincode: string;
}

export interface BookingDetailDto extends BookingListItem {
  address: BookingAddressDto;
  customerNotes: string | null;
  internalNotes: string | null;
  estimatedDuration: number;
  subtotal: number;
  taxRate: number;
  taxAmount: number;
  discount: number;
  items: BookingItemDto[];
  assignments: BookingAssignmentDto[];
  statusHistory: BookingStatusHistoryDto[];
  assignedAt: string | null;
  acceptedAt: string | null;
  startedAt: string | null;
  completedAt: string | null;
  cancelledAt: string | null;
  cancellationReason: string | null;
  updatedAt: string;
}

export interface BookingCatalogService {
  id: string;
  name: string;
  basePrice: number;
  duration: number;
  categoryName: string;
  items: { id: string; name: string; price: number; duration: number | null }[];
}
