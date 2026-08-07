"use client";

import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { useRouter } from "next/navigation";
import { toast } from "sonner";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import {
  createDocumentSchema,
  type CreateDocumentInput,
} from "@/features/employees/validators/employee.schema";

interface DocumentFormProps {
  employeeId: string;
  onSuccess?: () => void;
}

export function DocumentForm({ employeeId, onSuccess }: DocumentFormProps) {
  const router = useRouter();
  const {
    register,
    handleSubmit,
    formState: { errors, isSubmitting },
    reset,
  } = useForm<CreateDocumentInput>({
    resolver: zodResolver(createDocumentSchema),
    defaultValues: {
      type: "ID_PROOF",
      name: "",
      url: "",
    },
  });

  async function onSubmit(data: CreateDocumentInput) {
    try {
      const res = await fetch(`/api/employees/${employeeId}/documents`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(data),
      });

      const json = await res.json();
      if (!res.ok) throw new Error(json.message ?? "Failed to add document");

      toast.success("Document added");
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
          <Label htmlFor="docType">Type</Label>
          <select
            id="docType"
            {...register("type")}
            className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm"
          >
            <option value="ID_PROOF">ID Proof</option>
            <option value="ADDRESS_PROOF">Address Proof</option>
            <option value="CONTRACT">Contract</option>
            <option value="CERTIFICATE">Certificate</option>
            <option value="OTHER">Other</option>
          </select>
        </div>
        <div className="space-y-2">
          <Label htmlFor="docName">Document Name</Label>
          <Input id="docName" {...register("name")} />
          {errors.name && (
            <p className="text-sm text-destructive">{errors.name.message}</p>
          )}
        </div>
      </div>
      <div className="space-y-2">
        <Label htmlFor="docUrl">Document URL</Label>
        <Input id="docUrl" placeholder="https://..." {...register("url")} />
        {errors.url && (
          <p className="text-sm text-destructive">{errors.url.message}</p>
        )}
      </div>
      <Button type="submit" size="sm" disabled={isSubmitting}>
        {isSubmitting ? "Uploading..." : "Add Document"}
      </Button>
    </form>
  );
}
