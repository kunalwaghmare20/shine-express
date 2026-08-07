"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import { toast } from "sonner";
import { Button } from "@/components/ui/button";
import { Label } from "@/components/ui/label";
import type { EmployeeListItem } from "@/server/dto/employee.dto";

interface AssignStaffFormProps {
  bookingId: string;
  employees: EmployeeListItem[];
  onSuccess?: () => void;
}

export function AssignStaffForm({
  bookingId,
  employees,
  onSuccess,
}: AssignStaffFormProps) {
  const router = useRouter();
  const [selected, setSelected] = useState<string[]>([]);
  const [submitting, setSubmitting] = useState(false);

  function toggle(id: string) {
    setSelected((prev) =>
      prev.includes(id) ? prev.filter((x) => x !== id) : [...prev, id]
    );
  }

  async function onSubmit(e: React.FormEvent) {
    e.preventDefault();
    if (selected.length === 0) {
      toast.error("Select at least one staff member");
      return;
    }

    setSubmitting(true);
    try {
      const res = await fetch(`/api/bookings/${bookingId}/assign`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          employeeIds: selected,
          primaryEmployeeId: selected[0],
        }),
      });
      const json = await res.json();
      if (!res.ok) throw new Error(json.message ?? "Assignment failed");
      toast.success("Staff assigned");
      onSuccess?.();
      router.refresh();
    } catch (error) {
      toast.error(error instanceof Error ? error.message : "Something went wrong");
    } finally {
      setSubmitting(false);
    }
  }

  const available = employees.filter((e) => e.isAvailable && e.isActive);

  return (
    <form onSubmit={onSubmit} className="space-y-3">
      <Label>Assign Staff</Label>
      {available.length === 0 ? (
        <p className="text-sm text-muted-foreground">
          No available staff in this branch
        </p>
      ) : (
        <div className="max-h-48 space-y-2 overflow-y-auto rounded-lg border p-3">
          {available.map((emp) => (
            <label
              key={emp.id}
              className="flex cursor-pointer items-center gap-2 text-sm"
            >
              <input
                type="checkbox"
                checked={selected.includes(emp.id)}
                onChange={() => toggle(emp.id)}
                className="rounded"
              />
              <span>
                {emp.firstName} {emp.lastName}
                <span className="text-muted-foreground">
                  {" "}
                  · {emp.employeeCode}
                </span>
              </span>
            </label>
          ))}
        </div>
      )}
      <Button type="submit" size="sm" disabled={submitting || available.length === 0}>
        {submitting ? "Assigning..." : "Assign Staff"}
      </Button>
    </form>
  );
}
