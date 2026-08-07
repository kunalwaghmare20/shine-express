import { notFound } from "next/navigation";
import { ServiceDetailView } from "@/features/services/components/service-detail-view";
import {
  getServiceById,
  ServiceServiceError,
} from "@/server/services/service.service";

export default async function AdminServiceDetailPage({
  params,
}: {
  params: Promise<{ id: string }>;
}) {
  const { id } = await params;

  try {
    const service = await getServiceById(id);
    return <ServiceDetailView service={service} canEdit />;
  } catch (error) {
    if (error instanceof ServiceServiceError && error.statusCode === 404) {
      notFound();
    }
    throw error;
  }
}
