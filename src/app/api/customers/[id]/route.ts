import { NextRequest } from "next/server";
import {
  requireCustomerReadAccess,
  requireCustomerWriteAccess,
} from "@/lib/auth";
import { apiSuccess, handleApiError } from "@/lib/api";
import { updateCustomerSchema } from "@/features/customers/validators/customer.schema";
import {
  deleteCustomer,
  getCustomerById,
  updateCustomer,
} from "@/server/services/customer.service";

type RouteParams = { params: Promise<{ id: string }> };

export async function GET(_request: NextRequest, { params }: RouteParams) {
  try {
    await requireCustomerReadAccess();
    const { id } = await params;
    const customer = await getCustomerById(id);
    return apiSuccess(customer);
  } catch (error) {
    return handleApiError(error);
  }
}

export async function PATCH(request: NextRequest, { params }: RouteParams) {
  try {
    await requireCustomerWriteAccess();
    const { id } = await params;
    const body = await request.json();
    const input = updateCustomerSchema.parse(body);
    const customer = await updateCustomer(id, input);
    return apiSuccess(customer);
  } catch (error) {
    return handleApiError(error);
  }
}

export async function DELETE(_request: NextRequest, { params }: RouteParams) {
  try {
    await requireCustomerWriteAccess();
    const { id } = await params;
    await deleteCustomer(id);
    return apiSuccess({ deleted: true });
  } catch (error) {
    return handleApiError(error);
  }
}
