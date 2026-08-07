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
import { ServiceForm } from "@/features/services/components/service-form";
import type { CategoryOption } from "@/server/dto/service.dto";

interface CreateServiceDialogProps {
  categories: CategoryOption[];
  canCreate?: boolean;
}

export function CreateServiceDialog({
  categories,
  canCreate = true,
}: CreateServiceDialogProps) {
  const [open, setOpen] = useState(false);
  const router = useRouter();

  if (!canCreate) return null;

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        <Button disabled={categories.length === 0}>
          <Plus className="size-4" />
          Add Service
        </Button>
      </DialogTrigger>
      <DialogContent className="max-h-[90vh] overflow-y-auto">
        <DialogHeader>
          <DialogTitle>Add New Service</DialogTitle>
        </DialogHeader>
        {categories.length === 0 ? (
          <p className="text-sm text-muted-foreground">
            Create a category first before adding services.
          </p>
        ) : (
          <ServiceForm
            categories={categories}
            onSuccess={() => {
              setOpen(false);
              router.refresh();
            }}
          />
        )}
      </DialogContent>
    </Dialog>
  );
}
