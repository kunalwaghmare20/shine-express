import { NextRequest } from "next/server";
import {
  requireCustomerReadAccess,
  requireCustomerWriteAccess,
} from "@/lib/auth";
import { apiSuccess, handleApiError } from "@/lib/api";
import { createAddressSchema } from "@/features/customers/validators/customer.schema";
import {
  addCustomerAddress,
  getCustomerById,
} from "@/server/services/customer.service";

type RouteParams = { params: Promise<{ id: string }> };

export async function GET(_request: NextRequest, { params }: RouteParams) {
  try {
    await requireCustomerReadAccess();
    const { id } = await params;
    const customer = await getCustomerById(id);
    return apiSuccess(customer.addresses);
  } catch (error) {
    return handleApiError(error);
  }
}

export async function POST(request: NextRequest, { params }: RouteParams) {
  try {
    await requireCustomerWriteAccess();
    const { id } = await params;
    const body = await request.json();
    const input = createAddressSchema.parse(body);
    const address = await addCustomerAddress(id, input);
    return apiSuccess(address, 201);
  } catch (error) {
    return handleApiError(error);
  }
}
