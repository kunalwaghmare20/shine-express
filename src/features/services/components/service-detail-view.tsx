"use client";

import Link from "next/link";
import { useRouter } from "next/navigation";
import { toast } from "sonner";
import { ArrowLeft, Layers, ListTree, Trash2 } from "lucide-react";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Separator } from "@/components/ui/separator";
import { ServiceItemForm } from "@/features/services/components/service-item-form";
import type { ServiceDetailDto } from "@/server/dto/service.dto";
import { formatCurrency } from "@/lib/utils/format";

interface ServiceDetailViewProps {
  service: ServiceDetailDto;
  basePath?: string;
  canEdit?: boolean;
}

export function ServiceDetailView({
  service,
  basePath = "/admin/services",
  canEdit = true,
}: ServiceDetailViewProps) {
  const router = useRouter();

  async function toggleActive() {
    try {
      const res = await fetch(`/api/services/${service.id}`, {
        method: "PATCH",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ isActive: !service.isActive }),
      });
      const json = await res.json();
      if (!res.ok) throw new Error(json.message ?? "Update failed");
      toast.success(service.isActive ? "Service deactivated" : "Service activated");
      router.refresh();
    } catch (error) {
      toast.error(error instanceof Error ? error.message : "Something went wrong");
    }
  }

  async function removeItem(itemId: string) {
    if (!confirm("Delete this service item?")) return;
    try {
      const res = await fetch(`/api/services/${service.id}/items/${itemId}`, {
        method: "DELETE",
      });
      const json = await res.json();
      if (!res.ok) throw new Error(json.message ?? "Delete failed");
      toast.success("Item deleted");
      router.refresh();
    } catch (error) {
      toast.error(error instanceof Error ? error.message : "Something went wrong");
    }
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center gap-4">
        <Button variant="ghost" size="icon" asChild>
          <Link href={basePath}>
            <ArrowLeft className="size-4" />
          </Link>
        </Button>
        <div>
          <h2 className="text-2xl font-bold tracking-tight">{service.name}</h2>
          <p className="text-muted-foreground">{service.categoryName}</p>
        </div>
        <div className="ml-auto flex items-center gap-2">
          <Badge variant={service.isActive ? "success" : "secondary"}>
            {service.isActive ? "Active" : "Inactive"}
          </Badge>
          {canEdit && (
            <Button variant="outline" size="sm" onClick={toggleActive}>
              {service.isActive ? "Deactivate" : "Activate"}
            </Button>
          )}
        </div>
      </div>

      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-sm text-muted-foreground">Base Price</CardTitle>
          </CardHeader>
          <CardContent className="text-2xl font-bold">
            {formatCurrency(service.basePrice)}
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-sm text-muted-foreground">Duration</CardTitle>
          </CardHeader>
          <CardContent className="text-2xl font-bold">
            {service.duration} min
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-sm text-muted-foreground">Sub-items</CardTitle>
          </CardHeader>
          <CardContent className="text-2xl font-bold">
            {service.itemsCount}
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-sm text-muted-foreground">Slug</CardTitle>
          </CardHeader>
          <CardContent className="font-mono text-sm">{service.slug}</CardContent>
        </Card>
      </div>

      {service.description && (
        <Card>
          <CardHeader>
            <CardTitle className="text-base">Description</CardTitle>
          </CardHeader>
          <CardContent className="text-sm text-muted-foreground">
            {service.description}
          </CardContent>
        </Card>
      )}

      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2 text-base">
            <ListTree className="size-4" />
            Service Items (sub-services)
          </CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          {service.items.length === 0 ? (
            <p className="text-sm text-muted-foreground">
              No sub-items yet. Add options like Kitchen, Bathroom, Interior, etc.
            </p>
          ) : (
            <div className="space-y-2">
              {service.items.map((item) => (
                <div
                  key={item.id}
                  className="flex items-center justify-between rounded-lg border p-3 text-sm"
                >
                  <div>
                    <p className="font-medium">{item.name}</p>
                    <p className="text-xs text-muted-foreground">
                      {formatCurrency(item.price)}
                      {item.duration != null && ` · ${item.duration} min`}
                      {!item.isActive && " · Inactive"}
                    </p>
                  </div>
                  {canEdit && (
                    <Button
                      variant="ghost"
                      size="icon"
                      onClick={() => removeItem(item.id)}
                    >
                      <Trash2 className="size-4 text-destructive" />
                    </Button>
                  )}
                </div>
              ))}
            </div>
          )}

          {canEdit && (
            <>
              <Separator />
              <ServiceItemForm serviceId={service.id} />
            </>
          )}
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2 text-base">
            <Layers className="size-4" />
            Extensibility
          </CardTitle>
        </CardHeader>
        <CardContent className="text-sm text-muted-foreground">
          New services and sub-items are stored in MySQL — no code deploy required.
          Customers will see active services in the booking flow automatically.
        </CardContent>
      </Card>
    </div>
  );
}
