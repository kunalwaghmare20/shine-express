/**
 * Role-Based Access Control (RBAC) configuration.
 * Permissions are checked server-side in middleware and API routes.
 */

export enum UserRole {
  SUPER_ADMIN = "SUPER_ADMIN",
  BRANCH_MANAGER = "BRANCH_MANAGER",
  SERVICE_STAFF = "SERVICE_STAFF",
  CUSTOMER = "CUSTOMER",
}

export enum Permission {
  // Company & Branches
  MANAGE_COMPANY = "manage:company",
  MANAGE_BRANCHES = "manage:branches",

  // Users
  MANAGE_ALL_EMPLOYEES = "manage:all_employees",
  MANAGE_BRANCH_EMPLOYEES = "manage:branch_employees",
  VIEW_EMPLOYEES = "view:employees",

  // Customers
  MANAGE_ALL_CUSTOMERS = "manage:all_customers",
  VIEW_CUSTOMERS = "view:customers",

  // Services & Pricing
  MANAGE_SERVICES = "manage:services",
  MANAGE_PRICING = "manage:pricing",

  // Bookings
  MANAGE_ALL_BOOKINGS = "manage:all_bookings",
  MANAGE_BRANCH_BOOKINGS = "manage:branch_bookings",
  VIEW_ASSIGNED_JOBS = "view:assigned_jobs",
  UPDATE_JOB_STATUS = "update:job_status",
  CREATE_BOOKING = "create:booking",
  CANCEL_OWN_BOOKING = "cancel:own_booking",

  // Payments & Invoices
  MANAGE_PAYMENTS = "manage:payments",
  VIEW_INVOICES = "view:invoices",
  DOWNLOAD_INVOICE = "download:invoice",

  // Reports
  VIEW_ALL_REPORTS = "view:all_reports",
  VIEW_BRANCH_REPORTS = "view:branch_reports",

  // Settings
  MANAGE_SETTINGS = "manage:settings",
}

/** Maps each role to its allowed permissions */
export const ROLE_PERMISSIONS: Record<UserRole, Permission[]> = {
  [UserRole.SUPER_ADMIN]: Object.values(Permission),

  [UserRole.BRANCH_MANAGER]: [
    Permission.MANAGE_BRANCH_BOOKINGS,
    Permission.MANAGE_BRANCH_EMPLOYEES,
    Permission.VIEW_CUSTOMERS,
    Permission.VIEW_BRANCH_REPORTS,
    Permission.UPDATE_JOB_STATUS,
    Permission.VIEW_EMPLOYEES,
  ],

  [UserRole.SERVICE_STAFF]: [
    Permission.VIEW_ASSIGNED_JOBS,
    Permission.UPDATE_JOB_STATUS,
  ],

  [UserRole.CUSTOMER]: [
    Permission.CREATE_BOOKING,
    Permission.CANCEL_OWN_BOOKING,
    Permission.VIEW_INVOICES,
    Permission.DOWNLOAD_INVOICE,
  ],
};

/** Route prefixes accessible per role */
export const ROLE_ROUTE_PREFIX: Record<UserRole, string> = {
  [UserRole.SUPER_ADMIN]: "/admin",
  [UserRole.BRANCH_MANAGER]: "/branch-manager",
  [UserRole.SERVICE_STAFF]: "/staff",
  [UserRole.CUSTOMER]: "/book",
};

export function hasPermission(
  role: UserRole,
  permission: Permission
): boolean {
  return ROLE_PERMISSIONS[role]?.includes(permission) ?? false;
}
