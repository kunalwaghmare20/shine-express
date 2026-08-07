import { NextRequest } from "next/server";
import { requireCustomerWriteAccess } from "@/lib/auth";
import { apiSuccess, handleApiError } from "@/lib/api";
import { updateAddressSchema } from "@/features/customers/validators/customer.schema";
import {
  deleteCustomerAddress,
  updateCustomerAddress,
} from "@/server/services/customer.service";

type RouteParams = { params: Promise<{ id: string; addressId: string }> };

export async function PATCH(request: NextRequest, { params }: RouteParams) {
  try {
    await requireCustomerWriteAccess();
    const { id, addressId } = await params;
    const body = await request.json();
    const input = updateAddressSchema.parse(body);
    const address = await updateCustomerAddress(id, addressId, input);
    return apiSuccess(address);
  } catch (error) {
    return handleApiError(error);
  }
}

export async function DELETE(_request: NextRequest, { params }: RouteParams) {
  try {
    await requireCustomerWriteAccess();
    const { id, addressId } = await params;
    await deleteCustomerAddress(id, addressId);
    return apiSuccess({ deleted: true });
  } catch (error) {
    return handleApiError(error);
  }
}
