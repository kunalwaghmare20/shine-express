"use client";

import Link from "next/link";
import { useRouter, useSearchParams } from "next/navigation";
import { useCallback, useTransition } from "react";
import { Search } from "lucide-react";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { CreateServiceDialog } from "@/features/services/components/create-service-dialog";
import type { CategoryOption, ServiceListItem } from "@/server/dto/service.dto";
import type { PaginatedResult } from "@/server/dto/pagination.dto";
import { formatCurrency } from "@/lib/utils/format";

interface ServicesTableProps {
  data: PaginatedResult<ServiceListItem>;
  categories: CategoryOption[];
  basePath?: string;
  canCreate?: boolean;
}

export function ServicesTable({
  data,
  categories,
  basePath = "/admin/services",
  canCreate = true,
}: ServicesTableProps) {
  const router = useRouter();
  const searchParams = useSearchParams();
  const [isPending, startTransition] = useTransition();
  const search = searchParams.get("search") ?? "";
  const categoryId = searchParams.get("categoryId") ?? "";

  const updateParams = useCallback(
    (updates: Record<string, string | null>) => {
      const params = new URLSearchParams(searchParams.toString());
      Object.entries(updates).forEach(([key, value]) => {
        if (value === null || value === "") params.delete(key);
        else params.set(key, value);
      });
      startTransition(() => {
        router.push(`${basePath}?${params.toString()}`);
      });
    },
    [basePath, router, searchParams]
  );

  return (
    <div className="space-y-4">
      <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <div className="flex flex-1 flex-col gap-3 sm:flex-row">
          <form
            className="relative w-full max-w-sm"
            onSubmit={(e) => {
              e.preventDefault();
              const formData = new FormData(e.currentTarget);
              updateParams({
                search: (formData.get("search") as string) || null,
                page: "1",
              });
            }}
          >
            <Search className="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
            <Input
              name="search"
              defaultValue={search}
              placeholder="Search services..."
              className="pl-9"
            />
          </form>
          <select
            className="flex h-9 rounded-md border border-input bg-transparent px-3 text-sm"
            value={categoryId}
            onChange={(e) =>
              updateParams({ categoryId: e.target.value || null, page: "1" })
            }
          >
            <option value="">All categories</option>
            {categories.map((c) => (
              <option key={c.id} value={c.id}>
                {c.name}
              </option>
            ))}
          </select>
        </div>
        <CreateServiceDialog categories={categories} canCreate={canCreate} />
      </div>

      <div className="overflow-hidden rounded-xl border">
        <table className="w-full text-sm">
          <thead className="border-b bg-muted/40">
            <tr className="text-left text-muted-foreground">
              <th className="px-4 py-3 font-medium">Service</th>
              <th className="px-4 py-3 font-medium">Category</th>
              <th className="px-4 py-3 font-medium">Price</th>
              <th className="px-4 py-3 font-medium">Duration</th>
              <th className="px-4 py-3 font-medium">Items</th>
              <th className="px-4 py-3 font-medium">Status</th>
              <th className="px-4 py-3 font-medium" />
            </tr>
          </thead>
          <tbody>
            {data.items.length === 0 ? (
              <tr>
                <td
                  colSpan={7}
                  className="px-4 py-12 text-center text-muted-foreground"
                >
                  No services found
                </td>
              </tr>
            ) : (
              data.items.map((service) => (
                <tr key={service.id} className="border-b last:border-0">
                  <td className="px-4 py-3">
                    <div className="font-medium">{service.name}</div>
                    {service.description && (
                      <div className="line-clamp-1 text-xs text-muted-foreground">
                        {service.description}
                      </div>
                    )}
                  </td>
                  <td className="px-4 py-3">{service.categoryName}</td>
                  <td className="px-4 py-3">
                    {formatCurrency(service.basePrice)}
                  </td>
                  <td className="px-4 py-3">{service.duration} min</td>
                  <td className="px-4 py-3">{service.itemsCount}</td>
                  <td className="px-4 py-3">
                    <Badge variant={service.isActive ? "success" : "secondary"}>
                      {service.isActive ? "Active" : "Inactive"}
                    </Badge>
                  </td>
                  <td className="px-4 py-3 text-right">
                    <Button variant="outline" size="sm" asChild>
                      <Link href={`${basePath}/${service.id}`}>View</Link>
                    </Button>
                  </td>
                </tr>
              ))
            )}
          </tbody>
        </table>
      </div>

      <div className="flex items-center justify-between">
        <p className="text-sm text-muted-foreground">
          Page {data.page} of {data.totalPages} · {data.total} services
          {isPending && " · Loading..."}
        </p>
        <div className="flex gap-2">
          <Button
            variant="outline"
            size="sm"
            disabled={data.page <= 1 || isPending}
            onClick={() => updateParams({ page: String(data.page - 1) })}
          >
            Previous
          </Button>
          <Button
            variant="outline"
            size="sm"
            disabled={data.page >= data.totalPages || isPending}
            onClick={() => updateParams({ page: String(data.page + 1) })}
          >
            Next
          </Button>
        </div>
      </div>
    </div>
  );
}
