import { z } from "zod";

function slugify(value: string): string {
  return value
    .toLowerCase()
    .trim()
    .replace(/[^\w\s-]/g, "")
    .replace(/[\s_-]+/g, "-")
    .replace(/^-+|-+$/g, "");
}

export const createCategorySchema = z.object({
  name: z.string().min(1, "Name is required").max(255),
  slug: z.string().max(255).optional(),
  description: z.string().max(1000).optional().nullable(),
  icon: z.string().max(50).optional().nullable(),
  sortOrder: z.coerce.number().int().min(0),
  isActive: z.boolean(),
});

export const updateCategorySchema = createCategorySchema.partial();

export const createServiceSchema = z.object({
  categoryId: z.string().min(1, "Category is required"),
  name: z.string().min(1, "Name is required").max(255),
  slug: z.string().max(255).optional(),
  description: z.string().max(2000).optional().nullable(),
  basePrice: z.coerce.number().min(0, "Price must be >= 0"),
  duration: z.coerce.number().int().min(1, "Duration (minutes) is required"),
  images: z.array(z.string()),
  sortOrder: z.coerce.number().int().min(0),
  isActive: z.boolean(),
});

export const updateServiceSchema = createServiceSchema.partial();

export const createServiceItemSchema = z.object({
  name: z.string().min(1, "Name is required").max(255),
  description: z.string().max(1000).optional().nullable(),
  price: z.coerce.number().min(0, "Price must be >= 0"),
  duration: z.coerce.number().int().min(1).optional().nullable(),
  sortOrder: z.coerce.number().int().min(0),
  isActive: z.boolean(),
});

export const updateServiceItemSchema = createServiceItemSchema.partial();

export const serviceListQuerySchema = z.object({
  page: z.coerce.number().int().min(1).default(1),
  limit: z.coerce.number().int().min(1).max(100).default(20),
  search: z.string().optional(),
  categoryId: z.string().optional(),
  isActive: z.enum(["true", "false", "all"]).optional().default("all"),
  sort: z
    .enum(["createdAt", "name", "basePrice", "sortOrder"])
    .default("sortOrder"),
  order: z.enum(["asc", "desc"]).default("asc"),
});

export { slugify };

export type CreateCategoryInput = z.infer<typeof createCategorySchema>;
export type UpdateCategoryInput = z.infer<typeof updateCategorySchema>;
export type CreateServiceInput = z.infer<typeof createServiceSchema>;
export type UpdateServiceInput = z.infer<typeof updateServiceSchema>;
export type CreateServiceItemInput = z.infer<typeof createServiceItemSchema>;
export type UpdateServiceItemInput = z.infer<typeof updateServiceItemSchema>;
export type ServiceListQuery = z.infer<typeof serviceListQuerySchema>;
