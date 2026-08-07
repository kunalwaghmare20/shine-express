import { describe, expect, it } from "vitest";
import {
  assignStaffSchema,
  createBookingSchema,
  updateBookingStatusSchema,
} from "@/features/bookings/validators/booking.schema";
import {
  createAddressSchema,
  createCustomerSchema,
} from "@/features/customers/validators/customer.schema";
import { createServiceSchema } from "@/features/services/validators/service.schema";
import { reportsQuerySchema } from "@/features/reports/validators/reports.schema";
import { BookingStatus } from "@/types/booking";

describe("createBookingSchema", () => {
  const valid = {
    serviceId: "svc_1",
    addressId: "addr_1",
    branchId: "br_1",
    scheduledDate: "2026-08-01",
    scheduledTime: "10:00",
    serviceItemIds: ["item_1"],
  };

  it("accepts a valid booking payload", () => {
    expect(createBookingSchema.parse(valid)).toMatchObject(valid);
  });

  it("rejects missing required fields", () => {
    expect(() =>
      createBookingSchema.parse({ ...valid, serviceId: "" })
    ).toThrow();
  });
});

describe("updateBookingStatusSchema", () => {
  it("accepts valid statuses", () => {
    expect(
      updateBookingStatusSchema.parse({ status: BookingStatus.COMPLETED })
    ).toEqual({
      status: BookingStatus.COMPLETED,
      notes: undefined,
      cancellationReason: undefined,
    });
  });

  it("rejects unknown statuses", () => {
    expect(() =>
      updateBookingStatusSchema.parse({ status: "DONE" })
    ).toThrow();
  });
});

describe("assignStaffSchema", () => {
  it("requires at least one employee", () => {
    expect(() => assignStaffSchema.parse({ employeeIds: [] })).toThrow();
    expect(
      assignStaffSchema.parse({
        employeeIds: ["emp_1"],
        primaryEmployeeId: "emp_1",
      })
    ).toMatchObject({ employeeIds: ["emp_1"] });
  });
});

describe("createCustomerSchema", () => {
  it("validates email and phone", () => {
    expect(
      createCustomerSchema.parse({
        firstName: "Ada",
        lastName: "Lovelace",
        email: "ada@example.com",
        phone: "9876543210",
      })
    ).toMatchObject({ email: "ada@example.com" });

    expect(() =>
      createCustomerSchema.parse({
        firstName: "Ada",
        lastName: "Lovelace",
        email: "not-an-email",
        phone: "123",
      })
    ).toThrow();
  });
});

describe("createAddressSchema", () => {
  it("requires core address fields", () => {
    expect(
      createAddressSchema.parse({
        label: "Home",
        line1: "12 MG Road",
        city: "Pune",
        state: "MH",
        pincode: "411001",
        country: "India",
        isDefault: true,
      })
    ).toMatchObject({ city: "Pune" });
  });
});

describe("createServiceSchema", () => {
  it("coerces numeric price/duration", () => {
    const parsed = createServiceSchema.parse({
      categoryId: "cat_1",
      name: "House Cleaning",
      basePrice: "499.5",
      duration: "90",
      images: [],
      sortOrder: "0",
      isActive: true,
    });

    expect(parsed.basePrice).toBe(499.5);
    expect(parsed.duration).toBe(90);
  });

  it("rejects negative prices", () => {
    expect(() =>
      createServiceSchema.parse({
        categoryId: "cat_1",
        name: "Bad",
        basePrice: -1,
        duration: 30,
        images: [],
        sortOrder: 0,
        isActive: true,
      })
    ).toThrow();
  });
});

describe("reportsQuerySchema", () => {
  it("defaults period to monthly", () => {
    expect(reportsQuerySchema.parse({})).toEqual({
      period: "monthly",
      branchId: undefined,
      date: undefined,
    });
  });

  it("accepts known periods", () => {
    expect(reportsQuerySchema.parse({ period: "weekly" }).period).toBe(
      "weekly"
    );
  });
});
