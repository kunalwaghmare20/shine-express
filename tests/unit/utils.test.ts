import { describe, expect, it } from "vitest";
import {
  getPaginationMeta,
  parsePaginationParams,
} from "@/lib/api/pagination";
import { toDecimalNumber } from "@/lib/utils/format";
import { cn } from "@/lib/utils";

describe("parsePaginationParams", () => {
  it("applies defaults", () => {
    expect(parsePaginationParams(new URLSearchParams())).toEqual({
      page: 1,
      limit: 20,
      search: undefined,
      sort: "createdAt",
      order: "desc",
    });
  });

  it("clamps invalid page/limit values", () => {
    const params = new URLSearchParams({
      page: "0",
      limit: "500",
      order: "asc",
    });
    expect(parsePaginationParams(params)).toMatchObject({
      page: 1,
      limit: 100,
      order: "asc",
    });
  });
});

describe("getPaginationMeta", () => {
  it("computes total pages", () => {
    expect(getPaginationMeta(45, 2, 20)).toEqual({
      page: 2,
      limit: 20,
      total: 45,
      totalPages: 3,
    });
  });

  it("returns at least one page when empty", () => {
    expect(getPaginationMeta(0, 1, 20).totalPages).toBe(1);
  });
});

describe("toDecimalNumber", () => {
  it("parses string and number decimals", () => {
    expect(toDecimalNumber("12.50")).toBe(12.5);
    expect(toDecimalNumber(10)).toBe(10);
  });
});

describe("cn", () => {
  it("merges class names and resolves conflicts", () => {
    expect(cn("px-2", "px-4")).toBe("px-4");
    expect(cn("text-sm", false && "hidden", "font-medium")).toBe(
      "text-sm font-medium"
    );
  });
});
