import { ZodError } from "zod";
import { AuthError, ForbiddenError } from "@/lib/auth";
import { CustomerServiceError } from "@/server/services/customer.service";
import { EmployeeServiceError } from "@/server/services/employee.service";
import { ServiceServiceError } from "@/server/services/service.service";
import { BookingServiceError } from "@/server/services/booking.service";
import { NotificationServiceError } from "@/server/services/notifications/notification.service";
import { apiError } from "./response";

export function handleApiError(error: unknown) {
  if (error instanceof ZodError) {
    const errors: Record<string, string[]> = {};
    error.errors.forEach((issue) => {
      const path = issue.path.join(".") || "body";
      errors[path] = [...(errors[path] ?? []), issue.message];
    });
    return apiError("Validation failed", 422, errors);
  }
  if (error instanceof AuthError) {
    return apiError(error.message, error.statusCode);
  }

  if (error instanceof ForbiddenError) {
    return apiError(error.message, error.statusCode);
  }

  if (error instanceof CustomerServiceError) {
    return apiError(error.message, error.statusCode);
  }

  if (error instanceof EmployeeServiceError) {
    return apiError(error.message, error.statusCode);
  }

  if (error instanceof ServiceServiceError) {
    return apiError(error.message, error.statusCode);
  }

  if (error instanceof BookingServiceError) {
    return apiError(error.message, error.statusCode);
  }

  if (error instanceof NotificationServiceError) {
    return apiError(error.message, error.statusCode);
  }

  console.error("API error:", error);
  return apiError("Internal server error", 500);
}
