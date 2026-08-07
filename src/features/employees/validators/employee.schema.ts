import { z } from "zod";

const employeeRoles = ["SERVICE_STAFF", "BRANCH_MANAGER"] as const;

export const createEmployeeSchema = z.object({
  firstName: z.string().min(1).max(100),
  lastName: z.string().min(1).max(100),
  email: z.string().email(),
  phone: z.string().min(10).max(20),
  role: z.enum(employeeRoles),
  branchId: z.string().min(1, "Branch is required"),
  salary: z.coerce.number().min(0).optional().nullable(),
  skills: z.array(z.string()),
  isAvailable: z.boolean(),
});

export const updateEmployeeSchema = createEmployeeSchema
  .partial()
  .omit({ email: true })
  .extend({
    email: z.string().email().optional(),
    isActive: z.boolean().optional(),
  });

export const createDocumentSchema = z.object({
  type: z.enum([
    "ID_PROOF",
    "ADDRESS_PROOF",
    "CONTRACT",
    "CERTIFICATE",
    "OTHER",
  ]),
  name: z.string().min(1).max(255),
  url: z.string().url("Must be a valid URL"),
});

export const updateLocationSchema = z.object({
  latitude: z.string().max(20),
  longitude: z.string().max(20),
});

export const employeeListQuerySchema = z.object({
  page: z.coerce.number().int().min(1).default(1),
  limit: z.coerce.number().int().min(1).max(100).default(20),
  search: z.string().optional(),
  branchId: z.string().optional(),
  sort: z.enum(["createdAt", "firstName", "employeeCode"]).default("createdAt"),
  order: z.enum(["asc", "desc"]).default("desc"),
});

export type CreateEmployeeInput = z.infer<typeof createEmployeeSchema>;
export type UpdateEmployeeInput = z.infer<typeof updateEmployeeSchema>;
export type CreateDocumentInput = z.infer<typeof createDocumentSchema>;
export type EmployeeListQuery = z.infer<typeof employeeListQuerySchema>;
