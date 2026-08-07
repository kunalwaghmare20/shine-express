"use client";

import { Plus } from "lucide-react";
import { useState } from "react";
import { useRouter } from "next/navigation";
import { Button } from "@/components/ui/button";
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from "@/components/ui/dialog";
import { EmployeeForm } from "@/features/employees/components/employee-form";
import type { BranchOption } from "@/server/dto/employee.dto";

interface CreateEmployeeDialogProps {
  branches: BranchOption[];
  defaultBranchId?: string;
  canCreate?: boolean;
}

export function CreateEmployeeDialog({
  branches,
  defaultBranchId,
  canCreate = true,
}: CreateEmployeeDialogProps) {
  const [open, setOpen] = useState(false);
  const router = useRouter();

  if (!canCreate) return null;

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        <Button>
          <Plus className="size-4" />
          Add Employee
        </Button>
      </DialogTrigger>
      <DialogContent className="max-h-[90vh] overflow-y-auto">
        <DialogHeader>
          <DialogTitle>Add New Employee</DialogTitle>
        </DialogHeader>
        <EmployeeForm
          branches={branches}
          defaultBranchId={defaultBranchId}
          onSuccess={() => {
            setOpen(false);
            router.refresh();
          }}
        />
      </DialogContent>
    </Dialog>
  );
}
