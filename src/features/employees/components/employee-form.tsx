"use client";

import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { useRouter } from "next/navigation";
import { toast } from "sonner";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import {
  createEmployeeSchema,
  type CreateEmployeeInput,
} from "@/features/employees/validators/employee.schema";
import type { BranchOption } from "@/server/dto/employee.dto";

interface EmployeeFormProps {
  branches: BranchOption[];
  defaultBranchId?: string;
  onSuccess?: () => void;
}

export function EmployeeForm({
  branches,
  defaultBranchId,
  onSuccess,
}: EmployeeFormProps) {
  const router = useRouter();
  const {
    register,
    handleSubmit,
    formState: { errors, isSubmitting },
  } = useForm<CreateEmployeeInput>({
    resolver: zodResolver(createEmployeeSchema),
    defaultValues: {
      firstName: "",
      lastName: "",
      email: "",
      phone: "",
      role: "SERVICE_STAFF",
      branchId: defaultBranchId ?? branches[0]?.id ?? "",
      salary: undefined,
      skills: [],
      isAvailable: true,
    },
  });

  async function onSubmit(data: CreateEmployeeInput) {
    try {
      const skills = data.skills.length
        ? data.skills
        : (document.getElementById("skillsInput") as HTMLInputElement)?.value
            .split(",")
            .map((s) => s.trim())
            .filter(Boolean) ?? [];

      const res = await fetch("/api/employees", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ ...data, skills }),
      });

      const json = await res.json();
      if (!res.ok) throw new Error(json.message ?? "Failed to create employee");

      toast.success("Employee created");
      onSuccess?.();
      router.push(`/admin/employees/${json.data.id}`);
      router.refresh();
    } catch (error) {
      toast.error(error instanceof Error ? error.message : "Something went wrong");
    }
  }

  return (
    <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
      <div className="grid gap-4 sm:grid-cols-2">
        <div className="space-y-2">
          <Label htmlFor="firstName">First Name</Label>
          <Input id="firstName" {...register("firstName")} />
          {errors.firstName && (
            <p className="text-sm text-destructive">{errors.firstName.message}</p>
          )}
        </div>
        <div className="space-y-2">
          <Label htmlFor="lastName">Last Name</Label>
          <Input id="lastName" {...register("lastName")} />
          {errors.lastName && (
            <p className="text-sm text-destructive">{errors.lastName.message}</p>
          )}
        </div>
      </div>

      <div className="grid gap-4 sm:grid-cols-2">
        <div className="space-y-2">
          <Label htmlFor="email">Email</Label>
          <Input id="email" type="email" {...register("email")} />
          {errors.email && (
            <p className="text-sm text-destructive">{errors.email.message}</p>
          )}
        </div>
        <div className="space-y-2">
          <Label htmlFor="phone">Phone</Label>
          <Input id="phone" {...register("phone")} />
          {errors.phone && (
            <p className="text-sm text-destructive">{errors.phone.message}</p>
          )}
        </div>
      </div>

      <div className="grid gap-4 sm:grid-cols-2">
        <div className="space-y-2">
          <Label htmlFor="role">Role</Label>
          <select
            id="role"
            {...register("role")}
            className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm"
          >
            <option value="SERVICE_STAFF">Service Staff</option>
            <option value="BRANCH_MANAGER">Branch Manager</option>
          </select>
        </div>
        <div className="space-y-2">
          <Label htmlFor="branchId">Branch</Label>
          <select
            id="branchId"
            {...register("branchId")}
            className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm"
            disabled={!!defaultBranchId}
          >
            {branches.map((b) => (
              <option key={b.id} value={b.id}>
                {b.name} ({b.code})
              </option>
            ))}
          </select>
          {errors.branchId && (
            <p className="text-sm text-destructive">{errors.branchId.message}</p>
          )}
        </div>
      </div>

      <div className="grid gap-4 sm:grid-cols-2">
        <div className="space-y-2">
          <Label htmlFor="salary">Salary (optional)</Label>
          <Input
            id="salary"
            type="number"
            step="0.01"
            {...register("salary")}
          />
        </div>
        <div className="space-y-2">
          <Label htmlFor="skillsInput">Skills (comma-separated)</Label>
          <Input
            id="skillsInput"
            placeholder="Cleaning, Pest Control, Deep Cleaning"
          />
        </div>
      </div>

      <label className="flex items-center gap-2 text-sm">
        <input type="checkbox" {...register("isAvailable")} className="rounded" />
        Available for job assignments
      </label>

      <Button type="submit" disabled={isSubmitting} className="w-full">
        {isSubmitting ? "Saving..." : "Create Employee"}
      </Button>
    </form>
  );
}
