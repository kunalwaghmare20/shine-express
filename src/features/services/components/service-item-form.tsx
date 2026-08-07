"use client";

import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { useRouter } from "next/navigation";
import { toast } from "sonner";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import {
  createServiceItemSchema,
  type CreateServiceItemInput,
} from "@/features/services/validators/service.schema";

interface ServiceItemFormProps {
  serviceId: string;
  onSuccess?: () => void;
}

export function ServiceItemForm({ serviceId, onSuccess }: ServiceItemFormProps) {
  const router = useRouter();
  const {
    register,
    handleSubmit,
    formState: { errors, isSubmitting },
    reset,
  } = useForm<CreateServiceItemInput>({
    resolver: zodResolver(createServiceItemSchema),
    defaultValues: {
      name: "",
      description: "",
      price: 0,
      duration: 30,
      sortOrder: 0,
      isActive: true,
    },
  });

  async function onSubmit(data: CreateServiceItemInput) {
    try {
      const res = await fetch(`/api/services/${serviceId}/items`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(data),
      });
      const json = await res.json();
      if (!res.ok) throw new Error(json.message ?? "Failed to add item");

      toast.success("Service item added");
      reset({
        name: "",
        description: "",
        price: 0,
        duration: 30,
        sortOrder: 0,
        isActive: true,
      });
      onSuccess?.();
      router.refresh();
    } catch (error) {
      toast.error(error instanceof Error ? error.message : "Something went wrong");
    }
  }

  return (
    <form onSubmit={handleSubmit(onSubmit)} className="space-y-3">
      <div className="grid gap-3 sm:grid-cols-2">
        <div className="space-y-2">
          <Label htmlFor="itemName">Item Name</Label>
          <Input id="itemName" {...register("name")} placeholder="Kitchen" />
          {errors.name && (
            <p className="text-sm text-destructive">{errors.name.message}</p>
          )}
        </div>
        <div className="space-y-2">
          <Label htmlFor="itemPrice">Price (₹)</Label>
          <Input
            id="itemPrice"
            type="number"
            step="0.01"
            {...register("price")}
          />
          {errors.price && (
            <p className="text-sm text-destructive">{errors.price.message}</p>
          )}
        </div>
      </div>
      <div className="space-y-2">
        <Label htmlFor="itemDuration">Duration (minutes, optional)</Label>
        <Input id="itemDuration" type="number" {...register("duration")} />
      </div>
      <Button type="submit" size="sm" disabled={isSubmitting}>
        {isSubmitting ? "Adding..." : "Add Item"}
      </Button>
    </form>
  );
}
