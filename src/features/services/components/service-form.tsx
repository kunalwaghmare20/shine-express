"use client";

import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { useRouter } from "next/navigation";
import { toast } from "sonner";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import {
  createServiceSchema,
  type CreateServiceInput,
} from "@/features/services/validators/service.schema";
import type { CategoryOption } from "@/server/dto/service.dto";

interface ServiceFormProps {
  categories: CategoryOption[];
  onSuccess?: () => void;
}

export function ServiceForm({ categories, onSuccess }: ServiceFormProps) {
  const router = useRouter();
  const {
    register,
    handleSubmit,
    formState: { errors, isSubmitting },
  } = useForm<CreateServiceInput>({
    resolver: zodResolver(createServiceSchema),
    defaultValues: {
      categoryId: categories[0]?.id ?? "",
      name: "",
      description: "",
      basePrice: 0,
      duration: 60,
      images: [],
      sortOrder: 0,
      isActive: true,
    },
  });

  async function onSubmit(data: CreateServiceInput) {
    try {
      const res = await fetch("/api/services", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(data),
      });
      const json = await res.json();
      if (!res.ok) throw new Error(json.message ?? "Failed to create service");

      toast.success("Service created");
      onSuccess?.();
      router.push(`/admin/services/${json.data.id}`);
      router.refresh();
    } catch (error) {
      toast.error(error instanceof Error ? error.message : "Something went wrong");
    }
  }

  return (
    <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
      <div className="space-y-2">
        <Label htmlFor="categoryId">Category</Label>
        <select
          id="categoryId"
          {...register("categoryId")}
          className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm"
        >
          {categories.map((c) => (
            <option key={c.id} value={c.id}>
              {c.name}
            </option>
          ))}
        </select>
        {errors.categoryId && (
          <p className="text-sm text-destructive">{errors.categoryId.message}</p>
        )}
      </div>

      <div className="space-y-2">
        <Label htmlFor="name">Service Name</Label>
        <Input id="name" {...register("name")} placeholder="House Cleaning" />
        {errors.name && (
          <p className="text-sm text-destructive">{errors.name.message}</p>
        )}
      </div>

      <div className="space-y-2">
        <Label htmlFor="description">Description</Label>
        <Textarea id="description" rows={3} {...register("description")} />
      </div>

      <div className="grid gap-4 sm:grid-cols-2">
        <div className="space-y-2">
          <Label htmlFor="basePrice">Base Price (₹)</Label>
          <Input
            id="basePrice"
            type="number"
            step="0.01"
            {...register("basePrice")}
          />
          {errors.basePrice && (
            <p className="text-sm text-destructive">{errors.basePrice.message}</p>
          )}
        </div>
        <div className="space-y-2">
          <Label htmlFor="duration">Duration (minutes)</Label>
          <Input id="duration" type="number" {...register("duration")} />
          {errors.duration && (
            <p className="text-sm text-destructive">{errors.duration.message}</p>
          )}
        </div>
      </div>

      <label className="flex items-center gap-2 text-sm">
        <input type="checkbox" {...register("isActive")} className="rounded" />
        Active (visible for booking)
      </label>

      <Button type="submit" disabled={isSubmitting} className="w-full">
        {isSubmitting ? "Saving..." : "Create Service"}
      </Button>
    </form>
  );
}
