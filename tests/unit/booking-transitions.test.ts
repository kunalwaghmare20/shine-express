import { describe, expect, it } from "vitest";
import {
  BookingStatus,
  BOOKING_STATUS_TRANSITIONS,
  canTransitionBookingStatus,
} from "@/types/booking";

describe("canTransitionBookingStatus", () => {
  it("allows the happy-path workflow", () => {
    const path: BookingStatus[] = [
      BookingStatus.PENDING,
      BookingStatus.CONFIRMED,
      BookingStatus.ASSIGNED,
      BookingStatus.ACCEPTED,
      BookingStatus.ON_THE_WAY,
      BookingStatus.STARTED,
      BookingStatus.COMPLETED,
    ];

    for (let i = 0; i < path.length - 1; i++) {
      expect(canTransitionBookingStatus(path[i], path[i + 1])).toBe(true);
    }
  });

  it("allows cancel from pending and confirmed", () => {
    expect(
      canTransitionBookingStatus(BookingStatus.PENDING, BookingStatus.CANCELLED)
    ).toBe(true);
    expect(
      canTransitionBookingStatus(
        BookingStatus.CONFIRMED,
        BookingStatus.CANCELLED
      )
    ).toBe(true);
  });

  it("allows reassignment after reject", () => {
    expect(
      canTransitionBookingStatus(BookingStatus.REJECTED, BookingStatus.ASSIGNED)
    ).toBe(true);
  });

  it("blocks invalid jumps", () => {
    expect(
      canTransitionBookingStatus(BookingStatus.PENDING, BookingStatus.COMPLETED)
    ).toBe(false);
    expect(
      canTransitionBookingStatus(BookingStatus.STARTED, BookingStatus.PENDING)
    ).toBe(false);
    expect(
      canTransitionBookingStatus(
        BookingStatus.COMPLETED,
        BookingStatus.CANCELLED
      )
    ).toBe(false);
  });

  it("has no transitions out of terminal completed/cancelled", () => {
    expect(BOOKING_STATUS_TRANSITIONS[BookingStatus.COMPLETED]).toEqual([]);
    expect(BOOKING_STATUS_TRANSITIONS[BookingStatus.CANCELLED]).toEqual([]);
  });
});
