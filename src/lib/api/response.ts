import { NextResponse } from "next/server";
import type { ApiResponse, PaginationMeta } from "@/types/api";

export function apiSuccess<T>(
  data: T,
  status = 200,
  meta?: PaginationMeta
): NextResponse {
  const body: ApiResponse<T> & { meta?: PaginationMeta } = {
    success: true,
    data,
  };

  if (meta) {
    body.meta = meta;
  }

  return NextResponse.json(body, { status });
}

export function apiError(
  message: string,
  status = 500,
  errors?: Record<string, string[]>
): NextResponse {
  return NextResponse.json(
    { success: false, message, errors, statusCode: status },
    { status }
  );
}
