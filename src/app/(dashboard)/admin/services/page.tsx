import { ServicesPage } from "@/features/services/components/services-page";

export default function AdminServicesPage({
  searchParams,
}: {
  searchParams: Promise<Record<string, string | string[] | undefined>>;
}) {
  return (
    <ServicesPage
      searchParams={searchParams}
      basePath="/admin/services"
      canCreate
    />
  );
}
