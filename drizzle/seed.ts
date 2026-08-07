import { and, eq } from "drizzle-orm";
import { getDb } from "../src/lib/db";
import {
  companies,
  branches,
  permissions,
  roles,
  rolePermissions,
  serviceCategories,
  services,
  serviceItems,
} from "../src/lib/db/schema";

const db = getDb();

type UserRoleSlug =
  | "SUPER_ADMIN"
  | "BRANCH_MANAGER"
  | "SERVICE_STAFF"
  | "CUSTOMER";

const PERMISSIONS = [
  { slug: "manage:company", name: "Manage Company", module: "company" },
  { slug: "manage:branches", name: "Manage Branches", module: "branches" },
  {
    slug: "manage:all_employees",
    name: "Manage All Employees",
    module: "employees",
  },
  {
    slug: "manage:branch_employees",
    name: "Manage Branch Employees",
    module: "employees",
  },
  { slug: "view:employees", name: "View Employees", module: "employees" },
  {
    slug: "manage:all_customers",
    name: "Manage All Customers",
    module: "customers",
  },
  { slug: "view:customers", name: "View Customers", module: "customers" },
  { slug: "manage:services", name: "Manage Services", module: "services" },
  { slug: "manage:pricing", name: "Manage Pricing", module: "services" },
  {
    slug: "manage:all_bookings",
    name: "Manage All Bookings",
    module: "bookings",
  },
  {
    slug: "manage:branch_bookings",
    name: "Manage Branch Bookings",
    module: "bookings",
  },
  {
    slug: "view:assigned_jobs",
    name: "View Assigned Jobs",
    module: "bookings",
  },
  {
    slug: "update:job_status",
    name: "Update Job Status",
    module: "bookings",
  },
  { slug: "create:booking", name: "Create Booking", module: "bookings" },
  {
    slug: "cancel:own_booking",
    name: "Cancel Own Booking",
    module: "bookings",
  },
  { slug: "manage:payments", name: "Manage Payments", module: "payments" },
  { slug: "view:invoices", name: "View Invoices", module: "invoices" },
  {
    slug: "download:invoice",
    name: "Download Invoice",
    module: "invoices",
  },
  { slug: "view:all_reports", name: "View All Reports", module: "reports" },
  {
    slug: "view:branch_reports",
    name: "View Branch Reports",
    module: "reports",
  },
  { slug: "manage:settings", name: "Manage Settings", module: "settings" },
] as const;

const ROLE_PERMISSIONS: Record<UserRoleSlug, string[]> = {
  SUPER_ADMIN: PERMISSIONS.map((p) => p.slug),
  BRANCH_MANAGER: [
    "manage:branch_bookings",
    "manage:branch_employees",
    "view:customers",
    "view:branch_reports",
    "update:job_status",
    "view:employees",
  ],
  SERVICE_STAFF: ["view:assigned_jobs", "update:job_status"],
  CUSTOMER: [
    "create:booking",
    "cancel:own_booking",
    "view:invoices",
    "download:invoice",
  ],
};

const SERVICE_CATALOG = [
  {
    category: {
      name: "House Cleaning",
      slug: "house-cleaning",
      description: "Complete home cleaning services",
      icon: "home",
    },
    service: {
      name: "House Cleaning",
      slug: "house-cleaning-standard",
      description: "Professional house cleaning for all room types",
      basePrice: "1499.00",
      duration: 180,
    },
    items: [
      { name: "Kitchen", price: "399.00", duration: 45 },
      { name: "Bathroom", price: "299.00", duration: 30 },
      { name: "Bedroom", price: "349.00", duration: 40 },
      { name: "Balcony", price: "199.00", duration: 20 },
    ],
  },
  {
    category: {
      name: "Car Cleaning",
      slug: "car-cleaning",
      description: "Interior and exterior car wash services",
      icon: "car",
    },
    service: {
      name: "Car Cleaning",
      slug: "car-cleaning-standard",
      description: "Professional car wash and detailing",
      basePrice: "799.00",
      duration: 90,
    },
    items: [
      { name: "Interior", price: "399.00", duration: 45 },
      { name: "Exterior", price: "299.00", duration: 30 },
      { name: "Premium", price: "599.00", duration: 60 },
    ],
  },
  {
    category: {
      name: "Water Tank Cleaning",
      slug: "water-tank-cleaning",
      description: "Underground and overhead tank cleaning",
      icon: "droplets",
    },
    service: {
      name: "Water Tank Cleaning",
      slug: "water-tank-cleaning-standard",
      description: "Hygienic water tank cleaning and sanitization",
      basePrice: "1999.00",
      duration: 120,
    },
    items: [
      { name: "Underground", price: "1499.00", duration: 90 },
      { name: "Overhead", price: "999.00", duration: 60 },
    ],
  },
  {
    category: {
      name: "Sofa Cleaning",
      slug: "sofa-cleaning",
      description: "Deep sofa and upholstery cleaning",
      icon: "sofa",
    },
    service: {
      name: "Sofa Cleaning",
      slug: "sofa-cleaning-standard",
      description: "Steam cleaning for sofas and upholstery",
      basePrice: "899.00",
      duration: 60,
    },
    items: [{ name: "Standard Sofa", price: "899.00", duration: 60 }],
  },
  {
    category: {
      name: "Carpet Cleaning",
      slug: "carpet-cleaning",
      description: "Professional carpet shampooing and cleaning",
      icon: "grid",
    },
    service: {
      name: "Carpet Cleaning",
      slug: "carpet-cleaning-standard",
      description: "Deep carpet cleaning and stain removal",
      basePrice: "699.00",
      duration: 45,
    },
    items: [{ name: "Standard Carpet", price: "699.00", duration: 45 }],
  },
  {
    category: {
      name: "Pest Control",
      slug: "pest-control",
      description: "Safe and effective pest control treatments",
      icon: "bug",
    },
    service: {
      name: "Pest Control",
      slug: "pest-control-standard",
      description: "General pest control for homes and offices",
      basePrice: "2499.00",
      duration: 90,
    },
    items: [
      { name: "Cockroach Treatment", price: "1499.00", duration: 60 },
      { name: "Termite Treatment", price: "4999.00", duration: 120 },
      { name: "General Pest Control", price: "2499.00", duration: 90 },
    ],
  },
  {
    category: {
      name: "Deep Cleaning",
      slug: "deep-cleaning",
      description: "Intensive deep cleaning for homes and offices",
      icon: "sparkles",
    },
    service: {
      name: "Deep Cleaning",
      slug: "deep-cleaning-standard",
      description: "Complete deep cleaning with sanitization",
      basePrice: "3999.00",
      duration: 360,
    },
    items: [
      { name: "1 BHK", price: "3999.00", duration: 240 },
      { name: "2 BHK", price: "5499.00", duration: 300 },
      { name: "3 BHK", price: "6999.00", duration: 360 },
    ],
  },
];

async function upsertPermission(perm: (typeof PERMISSIONS)[number]) {
  const existing = await db.query.permissions.findFirst({
    where: eq(permissions.slug, perm.slug),
  });

  if (existing) {
    await db
      .update(permissions)
      .set({ name: perm.name, module: perm.module })
      .where(eq(permissions.id, existing.id));
    return existing.id;
  }

  const [inserted] = await db.insert(permissions).values(perm).$returningId();
  return inserted.id;
}

async function seedRolesAndPermissions() {
  console.log("Seeding roles and permissions...");

  const permissionIds = new Map<string, string>();
  for (const perm of PERMISSIONS) {
    const id = await upsertPermission(perm);
    permissionIds.set(perm.slug, id);
  }

  const roleDefs: {
    slug: UserRoleSlug;
    name: string;
    description: string;
  }[] = [
    {
      slug: "SUPER_ADMIN",
      name: "Super Admin",
      description: "Full system access",
    },
    {
      slug: "BRANCH_MANAGER",
      name: "Branch Manager",
      description: "Branch-scoped management",
    },
    {
      slug: "SERVICE_STAFF",
      name: "Service Staff",
      description: "Field service execution",
    },
    {
      slug: "CUSTOMER",
      name: "Customer",
      description: "Customer self-service portal",
    },
  ];

  for (const roleDef of roleDefs) {
    const existing = await db.query.roles.findFirst({
      where: eq(roles.slug, roleDef.slug),
    });

    let roleId: string;
    if (existing) {
      await db
        .update(roles)
        .set({ name: roleDef.name, description: roleDef.description })
        .where(eq(roles.id, existing.id));
      roleId = existing.id;
    } else {
      const [inserted] = await db
        .insert(roles)
        .values({ ...roleDef, isSystem: true })
        .$returningId();
      roleId = inserted.id;
    }

    await db
      .delete(rolePermissions)
      .where(eq(rolePermissions.roleId, roleId));

    const slugs = ROLE_PERMISSIONS[roleDef.slug];
    await db.insert(rolePermissions).values(
      slugs.map((slug) => ({
        roleId,
        permissionId: permissionIds.get(slug)!,
      }))
    );
  }
}

async function seedCompanyAndBranch() {
  console.log("Seeding company and branch...");

  const existingCompany = await db.query.companies.findFirst({
    where: eq(companies.slug, "shine-express"),
  });

  let companyId: string;
  if (existingCompany) {
    companyId = existingCompany.id;
  } else {
    const [inserted] = await db
      .insert(companies)
      .values({
        name: "Shine Express",
        slug: "shine-express",
        email: "info@shineexpress.com",
        phone: "+91 9876543210",
        gstNumber: "29AABCU9603R1ZM",
        address: "123 Business Park, MG Road",
        city: "Bangalore",
        state: "Karnataka",
        pincode: "560001",
        settings: {
          currency: "INR",
          taxRate: 18,
          bookingPrefix: "SE",
          timezone: "Asia/Kolkata",
        },
      })
      .$returningId();
    companyId = inserted.id;
  }

  const existingBranch = await db.query.branches.findFirst({
    where: eq(branches.code, "BLR-001"),
  });

  if (!existingBranch) {
    await db.insert(branches).values({
      companyId,
      name: "Bangalore Main",
      code: "BLR-001",
      email: "bangalore@shineexpress.com",
      phone: "+91 9876543211",
      address: "123 Business Park, MG Road",
      city: "Bangalore",
      state: "Karnataka",
      pincode: "560001",
      latitude: 12.9716,
      longitude: 77.5946,
    });
  }
}

async function seedServices() {
  console.log("Seeding service catalog...");

  for (const [index, entry] of SERVICE_CATALOG.entries()) {
    const existingCategory = await db.query.serviceCategories.findFirst({
      where: eq(serviceCategories.slug, entry.category.slug),
    });

    let categoryId: string;
    if (existingCategory) {
      await db
        .update(serviceCategories)
        .set({
          name: entry.category.name,
          description: entry.category.description,
          icon: entry.category.icon,
          sortOrder: index,
        })
        .where(eq(serviceCategories.id, existingCategory.id));
      categoryId = existingCategory.id;
    } else {
      const [inserted] = await db
        .insert(serviceCategories)
        .values({ ...entry.category, sortOrder: index })
        .$returningId();
      categoryId = inserted.id;
    }

    const existingService = await db.query.services.findFirst({
      where: eq(services.slug, entry.service.slug),
    });

    let serviceId: string;
    if (existingService) {
      await db
        .update(services)
        .set({
          name: entry.service.name,
          description: entry.service.description,
          basePrice: entry.service.basePrice,
          duration: entry.service.duration,
          categoryId,
        })
        .where(eq(services.id, existingService.id));
      serviceId = existingService.id;
    } else {
      const [inserted] = await db
        .insert(services)
        .values({ ...entry.service, categoryId, sortOrder: 0 })
        .$returningId();
      serviceId = inserted.id;
    }

    for (const [itemIndex, item] of entry.items.entries()) {
      const existingItem = await db.query.serviceItems.findFirst({
        where: and(
          eq(serviceItems.serviceId, serviceId),
          eq(serviceItems.name, item.name)
        ),
      });

      if (existingItem) {
        await db
          .update(serviceItems)
          .set({
            price: item.price,
            duration: item.duration,
            sortOrder: itemIndex,
          })
          .where(eq(serviceItems.id, existingItem.id));
      } else {
        await db.insert(serviceItems).values({
          serviceId,
          name: item.name,
          price: item.price,
          duration: item.duration,
          sortOrder: itemIndex,
        });
      }
    }
  }
}

async function main() {
  console.log("Starting database seed...\n");

  await seedRolesAndPermissions();
  await seedCompanyAndBranch();
  await seedServices();

  console.log("\nSeed completed successfully.");
  process.exit(0);
}

main().catch((error) => {
  console.error("Seed failed:", error);
  process.exit(1);
});
