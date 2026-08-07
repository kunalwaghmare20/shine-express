import { eq, inArray } from "drizzle-orm";
import { getDb } from "@/lib/db";
import { customers, employees } from "@/lib/db/schema";
import { notify } from "./notification.service";
import type { BookingDetailDto } from "@/server/dto/booking.dto";
import type { NotificationType } from "./types";
import { BookingStatus } from "@/types/booking";

async function getCustomerUserId(customerId: string): Promise<string | null> {
  const db = getDb();
  const customer = await db.query.customers.findFirst({
    where: eq(customers.id, customerId),
  });
  return customer?.userId ?? null;
}

async function getEmployeeUserIds(employeeIds: string[]): Promise<string[]> {
  if (employeeIds.length === 0) return [];
  const db = getDb();
  const rows = await db
    .select({ userId: employees.userId })
    .from(employees)
    .where(inArray(employees.id, employeeIds));
  return rows.map((r) => r.userId);
}

function bookingMeta(booking: BookingDetailDto) {
  return {
    bookingId: booking.id,
    bookingNumber: booking.bookingNumber,
    status: booking.status,
  };
}

/**
 * Emits notifications for booking lifecycle events.
 * Failures are swallowed so booking ops never fail due to notifications.
 */
export async function notifyBookingCreated(
  booking: BookingDetailDto
): Promise<void> {
  try {
    const customerUserId = await getCustomerUserId(booking.customerId);
    if (!customerUserId) return;

    await notify({
      userId: customerUserId,
      title: "Booking created",
      body: `Your booking ${booking.bookingNumber} for ${booking.serviceName} on ${booking.scheduledDate} at ${booking.scheduledTime} is pending confirmation.`,
      type: "BOOKING_CREATED",
      metadata: bookingMeta(booking),
      channels: ["IN_APP", "EMAIL"],
    });
  } catch (error) {
    console.error("[notifyBookingCreated]", error);
  }
}

export async function notifyBookingStatusChanged(
  booking: BookingDetailDto,
  status: BookingStatus
): Promise<void> {
  try {
    const customerUserId = await getCustomerUserId(booking.customerId);
    if (!customerUserId) return;

    const map: Partial<
      Record<
        BookingStatus,
        { type: NotificationType; title: string; body: string }
      >
    > = {
      [BookingStatus.CONFIRMED]: {
        type: "BOOKING_CONFIRMED",
        title: "Booking confirmed",
        body: `Booking ${booking.bookingNumber} has been confirmed.`,
      },
      [BookingStatus.STARTED]: {
        type: "BOOKING_STARTED",
        title: "Service started",
        body: `Our team has started your ${booking.serviceName} service (${booking.bookingNumber}).`,
      },
      [BookingStatus.COMPLETED]: {
        type: "BOOKING_COMPLETED",
        title: "Service completed",
        body: `Your booking ${booking.bookingNumber} is complete. Thank you for choosing Shine Express.`,
      },
      [BookingStatus.CANCELLED]: {
        type: "BOOKING_CANCELLED",
        title: "Booking cancelled",
        body: `Booking ${booking.bookingNumber} has been cancelled.`,
      },
    };

    const template = map[status];
    if (!template) return;

    await notify({
      userId: customerUserId,
      title: template.title,
      body: template.body,
      type: template.type,
      metadata: bookingMeta(booking),
      channels: ["IN_APP", "EMAIL"],
    });
  } catch (error) {
    console.error("[notifyBookingStatusChanged]", error);
  }
}

export async function notifyBookingAssigned(
  booking: BookingDetailDto
): Promise<void> {
  try {
    const customerUserId = await getCustomerUserId(booking.customerId);
    const staffUserIds = await getEmployeeUserIds(
      booking.assignments.map((a) => a.employeeId)
    );

    const jobs: Promise<unknown>[] = [];

    if (customerUserId) {
      jobs.push(
        notify({
          userId: customerUserId,
          title: "Staff assigned",
          body: `Staff has been assigned to booking ${booking.bookingNumber}.`,
          type: "BOOKING_ASSIGNED",
          metadata: bookingMeta(booking),
          channels: ["IN_APP", "EMAIL"],
        })
      );
    }

    for (const userId of staffUserIds) {
      jobs.push(
        notify({
          userId,
          title: "New job assigned",
          body: `You have been assigned to ${booking.bookingNumber} — ${booking.serviceName} on ${booking.scheduledDate} at ${booking.scheduledTime}.`,
          type: "BOOKING_ASSIGNED",
          metadata: bookingMeta(booking),
          channels: ["IN_APP", "EMAIL"],
        })
      );
    }

    await Promise.allSettled(jobs);
  } catch (error) {
    console.error("[notifyBookingAssigned]", error);
  }
}
