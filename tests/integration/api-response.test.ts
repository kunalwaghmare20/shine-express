import { describe, expect, it } from "vitest";
import { apiSuccess, apiError } from "@/lib/api/response";

describe("API response helpers", () => {
  it("wraps success payloads", async () => {
    const res = apiSuccess({ id: "1" }, 201);
    expect(res.status).toBe(201);
    const body = await res.json();
    expect(body).toEqual({ success: true, data: { id: "1" } });
  });

  it("includes pagination meta when provided", async () => {
    const res = apiSuccess([], 200, {
      page: 1,
      limit: 20,
      total: 0,
      totalPages: 1,
    });
    const body = await res.json();
    expect(body.meta).toEqual({
      page: 1,
      limit: 20,
      total: 0,
      totalPages: 1,
    });
  });

  it("formats error responses", async () => {
    const res = apiError("Validation failed", 422, {
      email: ["Invalid email"],
    });
    expect(res.status).toBe(422);
    const body = await res.json();
    expect(body).toMatchObject({
      success: false,
      message: "Validation failed",
      statusCode: 422,
      errors: { email: ["Invalid email"] },
    });
  });
});
