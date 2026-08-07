import { z } from "zod";
import { BookingStatus } from "@/types/booking";

export const createBookingSchema = z.object({
  serviceId: z.string().min(1, "Service is required"),
  addressId: z.string().min(1, "Address is required"),
  branchId: z.string().min(1, "Branch is required"),
  scheduledDate: z.string().min(1, "Date is required"),
  scheduledTime: z.string().min(1, "Time is required"),
  customerNotes: z.string().max(2000).optional().nullable(),
  serviceItemIds: z.array(z.string()),
  customerId: z.string().optional(),
});

export const updateBookingStatusSchema = z.object({
  status: z.nativeEnum(BookingStatus),
  notes: z.string().max(500).optional().nullable(),
  cancellationReason: z.string().max(500).optional().nullable(),
});

export const assignStaffSchema = z.object({
  employeeIds: z.array(z.string().min(1)).min(1, "Select at least one staff member"),
  primaryEmployeeId: z.string().optional(),
});

export const bookingListQuerySchema = z.object({
  page: z.coerce.number().int().min(1).default(1),
  limit: z.coerce.number().int().min(1).max(100).default(20),
  search: z.string().optional(),
  status: z.nativeEnum(BookingStatus).optional(),
  branchId: z.string().optional(),
  customerId: z.string().optional(),
  employeeId: z.string().optional(),
  dateFrom: z.string().optional(),
  dateTo: z.string().optional(),
  sort: z.enum(["createdAt", "scheduledDate", "totalAmount"]).default("createdAt"),
  order: z.enum(["asc", "desc"]).default("desc"),
});

export type CreateBookingInput = z.infer<typeof createBookingSchema>;
export type UpdateBookingStatusInput = z.infer<typeof updateBookingStatusSchema>;
export type AssignStaffInput = z.infer<typeof assignStaffSchema>;
export type BookingListQuery = z.infer<typeof bookingListQuerySchema>;
