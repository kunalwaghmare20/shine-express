/**
 * Booking lifecycle statuses.
 * Stored as MySQL enum via Drizzle schema (Module 2).
 */
export enum BookingStatus {
  PENDING = "PENDING",
  CONFIRMED = "CONFIRMED",
  ASSIGNED = "ASSIGNED",
  ACCEPTED = "ACCEPTED",
  ON_THE_WAY = "ON_THE_WAY",
  STARTED = "STARTED",
  COMPLETED = "COMPLETED",
  CANCELLED = "CANCELLED",
  REJECTED = "REJECTED",
}

export const BOOKING_STATUS_LABELS: Record<BookingStatus, string> = {
  [BookingStatus.PENDING]: "Pending",
  [BookingStatus.CONFIRMED]: "Confirmed",
  [BookingStatus.ASSIGNED]: "Assigned",
  [BookingStatus.ACCEPTED]: "Accepted",
  [BookingStatus.ON_THE_WAY]: "On The Way",
  [BookingStatus.STARTED]: "Started",
  [BookingStatus.COMPLETED]: "Completed",
  [BookingStatus.CANCELLED]: "Cancelled",
  [BookingStatus.REJECTED]: "Rejected",
};

/** Valid status transitions for workflow enforcement */
export const BOOKING_STATUS_TRANSITIONS: Record<
  BookingStatus,
  BookingStatus[]
> = {
  [BookingStatus.PENDING]: [BookingStatus.CONFIRMED, BookingStatus.CANCELLED],
  [BookingStatus.CONFIRMED]: [BookingStatus.ASSIGNED, BookingStatus.CANCELLED],
  [BookingStatus.ASSIGNED]: [BookingStatus.ACCEPTED, BookingStatus.REJECTED],
  [BookingStatus.ACCEPTED]: [BookingStatus.ON_THE_WAY, BookingStatus.REJECTED],
  [BookingStatus.ON_THE_WAY]: [BookingStatus.STARTED],
  [BookingStatus.STARTED]: [BookingStatus.COMPLETED],
  [BookingStatus.COMPLETED]: [],
  [BookingStatus.CANCELLED]: [],
  [BookingStatus.REJECTED]: [BookingStatus.ASSIGNED],
};

/** Returns true when `to` is an allowed next status from `from`. */
export function canTransitionBookingStatus(
  from: BookingStatus,
  to: BookingStatus
): boolean {
  return BOOKING_STATUS_TRANSITIONS[from]?.includes(to) ?? false;
}
