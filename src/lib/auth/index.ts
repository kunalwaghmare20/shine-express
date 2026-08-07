export {
  getCurrentUser,
  requireAuth,
  requireRole,
  requirePermission,
  getUserDisplayName,
  AuthError,
  ForbiddenError,
} from "./session";

export {
  syncUserFromClerk,
  deleteUserByClerkId,
  getDefaultRouteForRole,
  parseUserRole,
} from "./sync-user";

export {
  requireCustomerReadAccess,
  requireCustomerWriteAccess,
} from "./customer-access";

export {
  requireEmployeeReadAccess,
  requireEmployeeWriteAccess,
  assertBranchAccess,
} from "./employee-access";

export type { EmployeeAccessContext } from "./employee-access";

export {
  requireServiceReadAccess,
  requireServiceWriteAccess,
} from "./service-access";

export {
  requireBookingAccess,
  requireBookingManageAccess,
} from "./booking-access";

export type { BookingAccessContext } from "./booking-access";

export type { SyncUserInput } from "./sync-user";
