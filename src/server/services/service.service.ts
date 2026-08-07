import {
  and,
  asc,
  count,
  desc,
  eq,
  like,
  or,
  sql,
} from "drizzle-orm";
import { getDb } from "@/lib/db";
import {
  serviceCategories,
  serviceItems,
  services,
} from "@/lib/db/schema";
import {
  slugify,
  type CreateCategoryInput,
  type CreateServiceInput,
  type CreateServiceItemInput,
  type ServiceListQuery,
  type UpdateCategoryInput,
  type UpdateServiceInput,
  type UpdateServiceItemInput,
} from "@/features/services/validators/service.schema";
import type {
  CategoryOption,
  ServiceCategoryDto,
  ServiceDetailDto,
  ServiceItemDto,
  ServiceListItem,
} from "@/server/dto/service.dto";
import type { PaginatedResult } from "@/server/dto/pagination.dto";
import { toDecimalNumber } from "@/lib/utils/format";

export class ServiceServiceError extends Error {
  constructor(
    message: string,
    public statusCode: number = 400
  ) {
    super(message);
  }
}

function mapItem(row: typeof serviceItems.$inferSelect): ServiceItemDto {
  return {
    id: row.id,
    serviceId: row.serviceId,
    name: row.name,
    description: row.description,
    price: toDecimalNumber(row.price),
    duration: row.duration,
    sortOrder: row.sortOrder,
    isActive: row.isActive,
  };
}

async function uniqueSlug(
  table: "category" | "service",
  base: string,
  excludeId?: string
): Promise<string> {
  const db = getDb();
  let slug = slugify(base) || "service";
  let attempt = 0;

  while (true) {
    const candidate = attempt === 0 ? slug : `${slug}-${attempt}`;
    if (table === "category") {
      const existing = await db.query.serviceCategories.findFirst({
        where: eq(serviceCategories.slug, candidate),
      });
      if (!existing || existing.id === excludeId) return candidate;
    } else {
      const existing = await db.query.services.findFirst({
        where: eq(services.slug, candidate),
      });
      if (!existing || existing.id === excludeId) return candidate;
    }
    attempt += 1;
  }
}

// ─── Categories ──────────────────────────────────────────────────────────────

export async function listCategories(
  activeOnly = false
): Promise<ServiceCategoryDto[]> {
  const db = getDb();

  const rows = await db
    .select({
      id: serviceCategories.id,
      name: serviceCategories.name,
      slug: serviceCategories.slug,
      description: serviceCategories.description,
      icon: serviceCategories.icon,
      sortOrder: serviceCategories.sortOrder,
      isActive: serviceCategories.isActive,
      createdAt: serviceCategories.createdAt,
      servicesCount: sql<number>`(
        SELECT COUNT(*) FROM services s WHERE s.category_id = ${serviceCategories.id}
      )`.as("services_count"),
    })
    .from(serviceCategories)
    .where(activeOnly ? eq(serviceCategories.isActive, true) : undefined)
    .orderBy(asc(serviceCategories.sortOrder), asc(serviceCategories.name));

  return rows.map((row) => ({
    id: row.id,
    name: row.name,
    slug: row.slug,
    description: row.description,
    icon: row.icon,
    sortOrder: row.sortOrder,
    isActive: row.isActive,
    servicesCount: Number(row.servicesCount),
    createdAt: row.createdAt.toISOString(),
  }));
}

export async function listCategoryOptions(): Promise<CategoryOption[]> {
  const db = getDb();
  return db
    .select({
      id: serviceCategories.id,
      name: serviceCategories.name,
      slug: serviceCategories.slug,
    })
    .from(serviceCategories)
    .where(eq(serviceCategories.isActive, true))
    .orderBy(asc(serviceCategories.sortOrder), asc(serviceCategories.name));
}

export async function createCategory(
  input: CreateCategoryInput
): Promise<ServiceCategoryDto> {
  const db = getDb();
  const slug = await uniqueSlug("category", input.slug ?? input.name);

  const [inserted] = await db
    .insert(serviceCategories)
    .values({
      name: input.name,
      slug,
      description: input.description ?? null,
      icon: input.icon ?? null,
      sortOrder: input.sortOrder,
      isActive: input.isActive,
    })
    .$returningId();

  const categories = await listCategories();
  const created = categories.find((c) => c.id === inserted.id);
  if (!created) throw new ServiceServiceError("Failed to create category", 500);
  return created;
}

export async function updateCategory(
  id: string,
  input: UpdateCategoryInput
): Promise<ServiceCategoryDto> {
  const db = getDb();
  const existing = await db.query.serviceCategories.findFirst({
    where: eq(serviceCategories.id, id),
  });
  if (!existing) throw new ServiceServiceError("Category not found", 404);

  const slug =
    input.slug || input.name
      ? await uniqueSlug("category", input.slug ?? input.name ?? existing.name, id)
      : undefined;

  await db
    .update(serviceCategories)
    .set({
      ...(input.name !== undefined && { name: input.name }),
      ...(slug && { slug }),
      ...(input.description !== undefined && { description: input.description }),
      ...(input.icon !== undefined && { icon: input.icon }),
      ...(input.sortOrder !== undefined && { sortOrder: input.sortOrder }),
      ...(input.isActive !== undefined && { isActive: input.isActive }),
    })
    .where(eq(serviceCategories.id, id));

  const categories = await listCategories();
  const updated = categories.find((c) => c.id === id);
  if (!updated) throw new ServiceServiceError("Category not found", 404);
  return updated;
}

export async function deleteCategory(id: string): Promise<void> {
  const db = getDb();
  const existing = await db.query.serviceCategories.findFirst({
    where: eq(serviceCategories.id, id),
  });
  if (!existing) throw new ServiceServiceError("Category not found", 404);

  const [{ value }] = await db
    .select({ value: count() })
    .from(services)
    .where(eq(services.categoryId, id));

  if (value > 0) {
    throw new ServiceServiceError(
      "Cannot delete category with services. Move or delete services first.",
      409
    );
  }

  await db.delete(serviceCategories).where(eq(serviceCategories.id, id));
}

// ─── Services ────────────────────────────────────────────────────────────────

export async function listServices(
  query: ServiceListQuery
): Promise<PaginatedResult<ServiceListItem>> {
  const db = getDb();
  const { page, limit, search, categoryId, isActive, sort, order } = query;
  const offset = (page - 1) * limit;

  const searchFilter = search
    ? or(
        like(services.name, `%${search}%`),
        like(services.description, `%${search}%`),
        like(serviceCategories.name, `%${search}%`)
      )
    : undefined;

  const activeFilter =
    isActive === "true"
      ? eq(services.isActive, true)
      : isActive === "false"
        ? eq(services.isActive, false)
        : undefined;

  const whereClause = and(
    categoryId ? eq(services.categoryId, categoryId) : undefined,
    activeFilter,
    searchFilter
  );

  const orderFn = order === "asc" ? asc : desc;
  const orderBy =
    sort === "name"
      ? orderFn(services.name)
      : sort === "basePrice"
        ? orderFn(services.basePrice)
        : sort === "createdAt"
          ? orderFn(services.createdAt)
          : orderFn(services.sortOrder);

  const [rows, totalResult] = await Promise.all([
    db
      .select({
        id: services.id,
        categoryId: services.categoryId,
        categoryName: serviceCategories.name,
        name: services.name,
        slug: services.slug,
        description: services.description,
        basePrice: services.basePrice,
        duration: services.duration,
        images: services.images,
        isActive: services.isActive,
        sortOrder: services.sortOrder,
        createdAt: services.createdAt,
        itemsCount: sql<number>`(
          SELECT COUNT(*) FROM service_items si WHERE si.service_id = ${services.id}
        )`.as("items_count"),
      })
      .from(services)
      .innerJoin(
        serviceCategories,
        eq(services.categoryId, serviceCategories.id)
      )
      .where(whereClause)
      .orderBy(orderBy)
      .limit(limit)
      .offset(offset),

    db
      .select({ value: count() })
      .from(services)
      .innerJoin(
        serviceCategories,
        eq(services.categoryId, serviceCategories.id)
      )
      .where(whereClause),
  ]);

  const total = totalResult[0]?.value ?? 0;

  return {
    items: rows.map((row) => ({
      id: row.id,
      categoryId: row.categoryId,
      categoryName: row.categoryName,
      name: row.name,
      slug: row.slug,
      description: row.description,
      basePrice: toDecimalNumber(row.basePrice),
      duration: row.duration,
      images: (row.images as string[]) ?? [],
      isActive: row.isActive,
      sortOrder: row.sortOrder,
      itemsCount: Number(row.itemsCount),
      createdAt: row.createdAt.toISOString(),
    })),
    page,
    limit,
    total,
    totalPages: Math.ceil(total / limit) || 1,
  };
}

export async function getServiceById(id: string): Promise<ServiceDetailDto> {
  const db = getDb();

  const rows = await db
    .select({
      id: services.id,
      categoryId: services.categoryId,
      categoryName: serviceCategories.name,
      name: services.name,
      slug: services.slug,
      description: services.description,
      basePrice: services.basePrice,
      duration: services.duration,
      images: services.images,
      isActive: services.isActive,
      sortOrder: services.sortOrder,
      createdAt: services.createdAt,
      updatedAt: services.updatedAt,
    })
    .from(services)
    .innerJoin(
      serviceCategories,
      eq(services.categoryId, serviceCategories.id)
    )
    .where(eq(services.id, id))
    .limit(1);

  const row = rows[0];
  if (!row) throw new ServiceServiceError("Service not found", 404);

  const items = await db
    .select()
    .from(serviceItems)
    .where(eq(serviceItems.serviceId, id))
    .orderBy(asc(serviceItems.sortOrder), asc(serviceItems.name));

  return {
    id: row.id,
    categoryId: row.categoryId,
    categoryName: row.categoryName,
    name: row.name,
    slug: row.slug,
    description: row.description,
    basePrice: toDecimalNumber(row.basePrice),
    duration: row.duration,
    images: (row.images as string[]) ?? [],
    isActive: row.isActive,
    sortOrder: row.sortOrder,
    itemsCount: items.length,
    items: items.map(mapItem),
    createdAt: row.createdAt.toISOString(),
    updatedAt: row.updatedAt.toISOString(),
  };
}

export async function createService(
  input: CreateServiceInput
): Promise<ServiceDetailDto> {
  const db = getDb();

  const category = await db.query.serviceCategories.findFirst({
    where: eq(serviceCategories.id, input.categoryId),
  });
  if (!category) throw new ServiceServiceError("Category not found", 404);

  const slug = await uniqueSlug("service", input.slug ?? input.name);

  const [inserted] = await db
    .insert(services)
    .values({
      categoryId: input.categoryId,
      name: input.name,
      slug,
      description: input.description ?? null,
      basePrice: String(input.basePrice),
      duration: input.duration,
      images: input.images,
      sortOrder: input.sortOrder,
      isActive: input.isActive,
    })
    .$returningId();

  return getServiceById(inserted.id);
}

export async function updateService(
  id: string,
  input: UpdateServiceInput
): Promise<ServiceDetailDto> {
  const db = getDb();
  const existing = await getServiceById(id);

  if (input.categoryId) {
    const category = await db.query.serviceCategories.findFirst({
      where: eq(serviceCategories.id, input.categoryId),
    });
    if (!category) throw new ServiceServiceError("Category not found", 404);
  }

  const slug =
    input.slug || input.name
      ? await uniqueSlug("service", input.slug ?? input.name ?? existing.name, id)
      : undefined;

  await db
    .update(services)
    .set({
      ...(input.categoryId && { categoryId: input.categoryId }),
      ...(input.name !== undefined && { name: input.name }),
      ...(slug && { slug }),
      ...(input.description !== undefined && { description: input.description }),
      ...(input.basePrice !== undefined && {
        basePrice: String(input.basePrice),
      }),
      ...(input.duration !== undefined && { duration: input.duration }),
      ...(input.images !== undefined && { images: input.images }),
      ...(input.sortOrder !== undefined && { sortOrder: input.sortOrder }),
      ...(input.isActive !== undefined && { isActive: input.isActive }),
    })
    .where(eq(services.id, id));

  return getServiceById(id);
}

export async function deleteService(id: string): Promise<void> {
  const db = getDb();
  await getServiceById(id);

  await db.delete(serviceItems).where(eq(serviceItems.serviceId, id));
  await db.delete(services).where(eq(services.id, id));
}

// ─── Service Items ───────────────────────────────────────────────────────────

export async function addServiceItem(
  serviceId: string,
  input: CreateServiceItemInput
): Promise<ServiceItemDto> {
  const db = getDb();
  await getServiceById(serviceId);

  const [inserted] = await db
    .insert(serviceItems)
    .values({
      serviceId,
      name: input.name,
      description: input.description ?? null,
      price: String(input.price),
      duration: input.duration ?? null,
      sortOrder: input.sortOrder,
      isActive: input.isActive,
    })
    .$returningId();

  const item = await db.query.serviceItems.findFirst({
    where: eq(serviceItems.id, inserted.id),
  });

  return mapItem(item!);
}

export async function updateServiceItem(
  serviceId: string,
  itemId: string,
  input: UpdateServiceItemInput
): Promise<ServiceItemDto> {
  const db = getDb();
  await getServiceById(serviceId);

  const existing = await db.query.serviceItems.findFirst({
    where: and(eq(serviceItems.id, itemId), eq(serviceItems.serviceId, serviceId)),
  });
  if (!existing) throw new ServiceServiceError("Service item not found", 404);

  await db
    .update(serviceItems)
    .set({
      ...(input.name !== undefined && { name: input.name }),
      ...(input.description !== undefined && { description: input.description }),
      ...(input.price !== undefined && { price: String(input.price) }),
      ...(input.duration !== undefined && { duration: input.duration }),
      ...(input.sortOrder !== undefined && { sortOrder: input.sortOrder }),
      ...(input.isActive !== undefined && { isActive: input.isActive }),
    })
    .where(eq(serviceItems.id, itemId));

  const updated = await db.query.serviceItems.findFirst({
    where: eq(serviceItems.id, itemId),
  });

  return mapItem(updated!);
}

export async function deleteServiceItem(
  serviceId: string,
  itemId: string
): Promise<void> {
  const db = getDb();
  await getServiceById(serviceId);

  const existing = await db.query.serviceItems.findFirst({
    where: and(eq(serviceItems.id, itemId), eq(serviceItems.serviceId, serviceId)),
  });
  if (!existing) throw new ServiceServiceError("Service item not found", 404);

  await db.delete(serviceItems).where(eq(serviceItems.id, itemId));
}
