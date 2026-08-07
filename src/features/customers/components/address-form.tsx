"use client";

import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { useRouter } from "next/navigation";
import { toast } from "sonner";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import {
  createAddressSchema,
  type CreateAddressInput,
} from "@/features/customers/validators/customer.schema";

interface AddressFormProps {
  customerId: string;
  onSuccess?: () => void;
}

export function AddressForm({ customerId, onSuccess }: AddressFormProps) {
  const router = useRouter();
  const {
    register,
    handleSubmit,
    formState: { errors, isSubmitting },
    reset,
  } = useForm<CreateAddressInput>({
    resolver: zodResolver(createAddressSchema),
    defaultValues: {
      label: "Home",
      line1: "",
      line2: "",
      city: "",
      state: "",
      pincode: "",
      country: "India",
      isDefault: false,
    },
  });

  async function onSubmit(data: CreateAddressInput) {
    try {
      const res = await fetch(`/api/customers/${customerId}/addresses`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(data),
      });

      const json = await res.json();
      if (!res.ok) throw new Error(json.message ?? "Failed to add address");

      toast.success("Address added");
      reset();
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
          <Label htmlFor="label">Label</Label>
          <Input id="label" {...register("label")} placeholder="Home, Office..." />
        </div>
        <div className="space-y-2">
          <Label htmlFor="pincode">Pincode</Label>
          <Input id="pincode" {...register("pincode")} />
          {errors.pincode && (
            <p className="text-sm text-destructive">{errors.pincode.message}</p>
          )}
        </div>
      </div>

      <div className="space-y-2">
        <Label htmlFor="line1">Address Line 1</Label>
        <Input id="line1" {...register("line1")} />
        {errors.line1 && (
          <p className="text-sm text-destructive">{errors.line1.message}</p>
        )}
      </div>

      <div className="space-y-2">
        <Label htmlFor="line2">Address Line 2</Label>
        <Input id="line2" {...register("line2")} />
      </div>

      <div className="grid gap-3 sm:grid-cols-2">
        <div className="space-y-2">
          <Label htmlFor="city">City</Label>
          <Input id="city" {...register("city")} />
          {errors.city && (
            <p className="text-sm text-destructive">{errors.city.message}</p>
          )}
        </div>
        <div className="space-y-2">
          <Label htmlFor="state">State</Label>
          <Input id="state" {...register("state")} />
          {errors.state && (
            <p className="text-sm text-destructive">{errors.state.message}</p>
          )}
        </div>
      </div>

      <label className="flex items-center gap-2 text-sm">
        <input type="checkbox" {...register("isDefault")} className="rounded" />
        Set as default address
      </label>

      <Button type="submit" disabled={isSubmitting} size="sm">
        {isSubmitting ? "Adding..." : "Add Address"}
      </Button>
    </form>
  );
}
