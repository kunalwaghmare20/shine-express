import { Suspense } from "react";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Skeleton } from "@/components/ui/skeleton";
import { Separator } from "@/components/ui/separator";
import { CategoryForm } from "@/features/services/components/category-form";
import { ServicesTable } from "@/features/services/components/services-table";
import { serviceListQuerySchema } from "@/features/services/validators/service.schema";
import {
  listCategories,
  listCategoryOptions,
  listServices,
} from "@/server/services/service.service";

interface ServicesPageProps {
  searchParams: Promise<Record<string, string | string[] | undefined>>;
  basePath?: string;
  canCreate?: boolean;
}

async function ServicesContent({
  searchParams,
  basePath,
  canCreate,
}: ServicesPageProps) {
  const params = await searchParams;
  const query = serviceListQuerySchema.parse({
    page: params.page,
    limit: params.limit,
    search: params.search,
    categoryId: params.categoryId,
    isActive: params.isActive,
    sort: params.sort,
    order: params.order,
  });

  const [data, categories, categoryOptions] = await Promise.all([
    listServices(query),
    listCategories(),
    listCategoryOptions(),
  ]);

  return (
    <div className="space-y-6">
      <div className="grid gap-6 lg:grid-cols-3">
        <Card className="lg:col-span-1">
          <CardHeader>
            <CardTitle className="text-base">
              Categories ({categories.length})
            </CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            {categories.length === 0 ? (
              <p className="text-sm text-muted-foreground">No categories yet</p>
            ) : (
              <div className="space-y-2">
                {categories.map((category) => (
                  <div
                    key={category.id}
                    className="flex items-center justify-between rounded-lg border p-3 text-sm"
                  >
                    <div>
                      <p className="font-medium">{category.name}</p>
                      <p className="text-xs text-muted-foreground">
                        {category.servicesCount} services
                      </p>
                    </div>
                    <Badge variant={category.isActive ? "success" : "secondary"}>
                      {category.isActive ? "Active" : "Off"}
                    </Badge>
                  </div>
                ))}
              </div>
            )}

            {canCreate && (
              <>
                <Separator />
                <CategoryForm />
              </>
            )}
          </CardContent>
        </Card>

        <div className="lg:col-span-2">
          <ServicesTable
            data={data}
            categories={categoryOptions}
            basePath={basePath}
            canCreate={canCreate}
          />
        </div>
      </div>
    </div>
  );
}

function ServicesSkeleton() {
  return (
    <div className="grid gap-6 lg:grid-cols-3">
      <Skeleton className="h-80 w-full" />
      <Skeleton className="h-80 w-full lg:col-span-2" />
    </div>
  );
}

export function ServicesPage(props: ServicesPageProps) {
  return (
    <div className="space-y-6">
      <div>
        <h2 className="text-2xl font-bold tracking-tight">Services</h2>
        <p className="text-muted-foreground">
          Manage categories, services, and sub-items. Add new offerings without
          code changes.
        </p>
      </div>

      <Suspense fallback={<ServicesSkeleton />}>
        <ServicesContent {...props} />
      </Suspense>
    </div>
  );
}
