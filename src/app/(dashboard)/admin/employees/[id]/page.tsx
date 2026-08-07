import { notFound } from "next/navigation";
import { EmployeeDetailView } from "@/features/employees/components/employee-detail-view";
import {
  getEmployeeById,
  EmployeeServiceError,
} from "@/server/services/employee.service";

export default async function AdminEmployeeDetailPage({
  params,
}: {
  params: Promise<{ id: string }>;
}) {
  const { id } = await params;

  try {
    const employee = await getEmployeeById(id);
    return <EmployeeDetailView employee={employee} canEdit />;
  } catch (error) {
    if (error instanceof EmployeeServiceError && error.statusCode === 404) {
      notFound();
    }
    throw error;
  }
}
