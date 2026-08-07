import { describe, expect, it } from "vitest";
import {
  hasPermission,
  Permission,
  UserRole,
  ROLE_PERMISSIONS,
} from "@/config/roles";
import {
  getDefaultRouteForRole,
  parseUserRole,
} from "@/lib/auth/sync-user";

describe("RBAC hasPermission", () => {
  it("grants super admin every permission", () => {
    for (const permission of Object.values(Permission)) {
      expect(hasPermission(UserRole.SUPER_ADMIN, permission)).toBe(true);
    }
  });

  it("limits branch manager to branch-scoped permissions", () => {
    expect(
      hasPermission(UserRole.BRANCH_MANAGER, Permission.MANAGE_BRANCH_BOOKINGS)
    ).toBe(true);
    expect(
      hasPermission(UserRole.BRANCH_MANAGER, Permission.VIEW_BRANCH_REPORTS)
    ).toBe(true);
    expect(
      hasPermission(UserRole.BRANCH_MANAGER, Permission.MANAGE_COMPANY)
    ).toBe(false);
    expect(
      hasPermission(UserRole.BRANCH_MANAGER, Permission.MANAGE_ALL_BOOKINGS)
    ).toBe(false);
  });

  it("limits staff to assigned jobs and status updates", () => {
    expect(
      hasPermission(UserRole.SERVICE_STAFF, Permission.VIEW_ASSIGNED_JOBS)
    ).toBe(true);
    expect(
      hasPermission(UserRole.SERVICE_STAFF, Permission.UPDATE_JOB_STATUS)
    ).toBe(true);
    expect(
      hasPermission(UserRole.SERVICE_STAFF, Permission.MANAGE_BRANCH_BOOKINGS)
    ).toBe(false);
  });

  it("limits customers to booking self-service permissions", () => {
    expect(hasPermission(UserRole.CUSTOMER, Permission.CREATE_BOOKING)).toBe(
      true
    );
    expect(
      hasPermission(UserRole.CUSTOMER, Permission.CANCEL_OWN_BOOKING)
    ).toBe(true);
    expect(
      hasPermission(UserRole.CUSTOMER, Permission.MANAGE_SERVICES)
    ).toBe(false);
  });

  it("defines a non-empty permission list for every role", () => {
    for (const role of Object.values(UserRole)) {
      expect(ROLE_PERMISSIONS[role].length).toBeGreaterThan(0);
    }
  });
});

describe("parseUserRole / getDefaultRouteForRole", () => {
  it("parses known roles and defaults unknown values to CUSTOMER", () => {
    expect(parseUserRole("SUPER_ADMIN")).toBe(UserRole.SUPER_ADMIN);
    expect(parseUserRole("BRANCH_MANAGER")).toBe(UserRole.BRANCH_MANAGER);
    expect(parseUserRole("not-a-role")).toBe(UserRole.CUSTOMER);
    expect(parseUserRole(null)).toBe(UserRole.CUSTOMER);
    expect(parseUserRole(undefined)).toBe(UserRole.CUSTOMER);
  });

  it("maps roles to their portal route prefixes", () => {
    expect(getDefaultRouteForRole(UserRole.SUPER_ADMIN)).toBe("/admin");
    expect(getDefaultRouteForRole(UserRole.BRANCH_MANAGER)).toBe(
      "/branch-manager"
    );
    expect(getDefaultRouteForRole(UserRole.SERVICE_STAFF)).toBe("/staff");
    expect(getDefaultRouteForRole(UserRole.CUSTOMER)).toBe("/book");
  });
});
