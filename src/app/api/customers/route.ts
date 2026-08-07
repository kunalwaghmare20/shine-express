import { NextRequest } from "next/server";
import {
  requireCustomerReadAccess,
  requireCustomerWriteAccess,
} from "@/lib/auth";
import { apiSuccess, handleApiError } from "@/lib/api";
import { getPaginationMeta } from "@/lib/api/pagination";
import {
  createCustomerSchema,
  customerListQuerySchema,
} from "@/features/customers/validators/customer.schema";
import {
  createCustomer,
  listCustomers,
} from "@/server/services/customer.service";

export async function GET(request: NextRequest) {
  try {
    await requireCustomerReadAccess();

    const params = Object.fromEntries(request.nextUrl.searchParams);
    const query = customerListQuerySchema.parse(params);
    const result = await listCustomers(query);

    return apiSuccess(result.items, 200, getPaginationMeta(result.total, result.page, result.limit));
  } catch (error) {
    return handleApiError(error);
  }
}

export async function POST(request: NextRequest) {
  try {
    await requireCustomerWriteAccess();

    const body = await request.json();
    const input = createCustomerSchema.parse(body);
    const customer = await createCustomer(input);

    return apiSuccess(customer, 201);
  } catch (error) {
    return handleApiError(error);
  }
}
