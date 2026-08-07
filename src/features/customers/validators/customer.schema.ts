import { z } from "zod";

export const createCustomerSchema = z.object({
  firstName: z.string().min(1, "First name is required").max(100),
  lastName: z.string().min(1, "Last name is required").max(100),
  email: z.string().email("Invalid email address"),
  phone: z.string().min(10, "Phone must be at least 10 digits").max(20),
  gstNumber: z.string().max(20).optional().nullable(),
  notes: z.string().max(2000).optional().nullable(),
});

export const updateCustomerSchema = createCustomerSchema.partial();

export const createAddressSchema = z.object({
  label: z.string().min(1, "Label is required").max(50),
  line1: z.string().min(1, "Address line is required").max(255),
  line2: z.string().max(255).optional().nullable(),
  city: z.string().min(1, "City is required").max(100),
  state: z.string().min(1, "State is required").max(100),
  pincode: z.string().min(4, "Pincode is required").max(10),
  country: z.string().min(1).max(100),
  isDefault: z.boolean(),
});

export const updateAddressSchema = createAddressSchema.partial();

export const customerListQuerySchema = z.object({
  page: z.coerce.number().int().min(1).default(1),
  limit: z.coerce.number().int().min(1).max(100).default(20),
  search: z.string().optional(),
  sort: z
    .enum(["createdAt", "firstName", "email", "bookings"])
    .default("createdAt"),
  order: z.enum(["asc", "desc"]).default("desc"),
});

export type CreateCustomerInput = z.infer<typeof createCustomerSchema>;
export type UpdateCustomerInput = z.infer<typeof updateCustomerSchema>;
export type CreateAddressInput = z.infer<typeof createAddressSchema>;
export type UpdateAddressInput = z.infer<typeof updateAddressSchema>;
export type CustomerListQuery = z.infer<typeof customerListQuerySchema>;
