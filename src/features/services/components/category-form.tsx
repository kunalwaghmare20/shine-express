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
  createCategorySchema,
  type CreateCategoryInput,
} from "@/features/services/validators/service.schema";

interface CategoryFormProps {
  onSuccess?: () => void;
}

export function CategoryForm({ onSuccess }: CategoryFormProps) {
  const router = useRouter();
  const {
    register,
    handleSubmit,
    formState: { errors, isSubmitting },
    reset,
  } = useForm<CreateCategoryInput>({
    resolver: zodResolver(createCategorySchema),
    defaultValues: {
      name: "",
      description: "",
      icon: "",
      sortOrder: 0,
      isActive: true,
    },
  });

  async function onSubmit(data: CreateCategoryInput) {
    try {
      const res = await fetch("/api/services/categories", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(data),
      });
      const json = await res.json();
      if (!res.ok) throw new Error(json.message ?? "Failed to create category");

      toast.success("Category created");
      reset();
      onSuccess?.();
      router.refresh();
    } catch (error) {
      toast.error(error instanceof Error ? error.message : "Something went wrong");
    }
  }

  return (
    <form onSubmit={handleSubmit(onSubmit)} className="space-y-3">
      <div className="space-y-2">
        <Label htmlFor="catName">Name</Label>
        <Input id="catName" {...register("name")} placeholder="Pool Cleaning" />
        {errors.name && (
          <p className="text-sm text-destructive">{errors.name.message}</p>
        )}
      </div>
      <div className="space-y-2">
        <Label htmlFor="catDesc">Description</Label>
        <Textarea id="catDesc" rows={2} {...register("description")} />
      </div>
      <div className="grid gap-3 sm:grid-cols-2">
        <div className="space-y-2">
          <Label htmlFor="catIcon">Icon (optional)</Label>
          <Input id="catIcon" {...register("icon")} placeholder="droplets" />
        </div>
        <div className="space-y-2">
          <Label htmlFor="catSort">Sort Order</Label>
          <Input id="catSort" type="number" {...register("sortOrder")} />
        </div>
      </div>
      <Button type="submit" size="sm" disabled={isSubmitting}>
        {isSubmitting ? "Saving..." : "Add Category"}
      </Button>
    </form>
  );
}
