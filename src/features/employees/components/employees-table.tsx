"use client";

import Link from "next/link";
import { useRouter, useSearchParams } from "next/navigation";
import { useCallback, useTransition } from "react";
import { Search } from "lucide-react";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { CreateEmployeeDialog } from "@/features/employees/components/create-employee-dialog";
import type { BranchOption, EmployeeListItem } from "@/server/dto/employee.dto";
import type { PaginatedResult } from "@/server/dto/pagination.dto";

interface EmployeesTableProps {
  data: PaginatedResult<EmployeeListItem>;
  branches: BranchOption[];
  basePath?: string;
  canCreate?: boolean;
  defaultBranchId?: string;
}

export function EmployeesTable({
  data,
  branches,
  basePath = "/admin/employees",
  canCreate = true,
  defaultBranchId,
}: EmployeesTableProps) {
  const router = useRouter();
  const searchParams = useSearchParams();
  const [isPending, startTransition] = useTransition();
  const search = searchParams.get("search") ?? "";

  const updateParams = useCallback(
    (updates: Record<string, string | null>) => {
      const params = new URLSearchParams(searchParams.toString());
      Object.entries(updates).forEach(([key, value]) => {
        if (value === null || value === "") params.delete(key);
        else params.set(key, value);
      });
      startTransition(() => {
        router.push(`${basePath}?${params.toString()}`);
      });
    },
    [basePath, router, searchParams]
  );

  return (
    <div className="space-y-4">
      <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <form
          className="relative w-full max-w-sm"
          onSubmit={(e) => {
            e.preventDefault();
            const formData = new FormData(e.currentTarget);
            updateParams({
              search: (formData.get("search") as string) || null,
              page: "1",
            });
          }}
        >
          <Search className="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
          <Input
            name="search"
            defaultValue={search}
            placeholder="Search name, email, code..."
            className="pl-9"
          />
        </form>
        <CreateEmployeeDialog
          branches={branches}
          defaultBranchId={defaultBranchId}
          canCreate={canCreate}
        />
      </div>

      <div className="overflow-hidden rounded-xl border">
        <table className="w-full text-sm">
          <thead className="border-b bg-muted/40">
            <tr className="text-left text-muted-foreground">
              <th className="px-4 py-3 font-medium">Employee</th>
              <th className="px-4 py-3 font-medium">Branch</th>
              <th className="px-4 py-3 font-medium">Role</th>
              <th className="px-4 py-3 font-medium">Skills</th>
              <th className="px-4 py-3 font-medium">Jobs</th>
              <th className="px-4 py-3 font-medium">Status</th>
              <th className="px-4 py-3 font-medium" />
            </tr>
          </thead>
          <tbody>
            {data.items.length === 0 ? (
              <tr>
                <td colSpan={7} className="px-4 py-12 text-center text-muted-foreground">
                  No employees found
                </td>
              </tr>
            ) : (
              data.items.map((employee) => (
                <tr key={employee.id} className="border-b last:border-0">
                  <td className="px-4 py-3">
                    <div className="font-medium">
                      {employee.firstName} {employee.lastName}
                    </div>
                    <div className="text-xs text-muted-foreground">
                      {employee.employeeCode} · {employee.email}
                    </div>
                  </td>
                  <td className="px-4 py-3">{employee.branchName}</td>
                  <td className="px-4 py-3">
                    <Badge variant="outline">
                      {employee.role.replace("_", " ")}
                    </Badge>
                  </td>
                  <td className="px-4 py-3">
                    <div className="flex flex-wrap gap-1">
                      {employee.skills.slice(0, 2).map((skill) => (
                        <Badge key={skill} variant="secondary" className="text-xs">
                          {skill}
                        </Badge>
                      ))}
                      {employee.skills.length > 2 && (
                        <Badge variant="secondary" className="text-xs">
                          +{employee.skills.length - 2}
                        </Badge>
                      )}
                    </div>
                  </td>
                  <td className="px-4 py-3">{employee.jobsCount}</td>
                  <td className="px-4 py-3">
                    <div className="flex flex-col gap-1">
                      <Badge variant={employee.isActive ? "success" : "secondary"}>
                        {employee.isActive ? "Active" : "Inactive"}
                      </Badge>
                      <Badge variant={employee.isAvailable ? "outline" : "warning"}>
                        {employee.isAvailable ? "Available" : "Busy"}
                      </Badge>
                    </div>
                  </td>
                  <td className="px-4 py-3 text-right">
                    <Button variant="outline" size="sm" asChild>
                      <Link href={`${basePath}/${employee.id}`}>View</Link>
                    </Button>
                  </td>
                </tr>
              ))
            )}
          </tbody>
        </table>
      </div>

      <div className="flex items-center justify-between">
        <p className="text-sm text-muted-foreground">
          Page {data.page} of {data.totalPages} · {data.total} employees
          {isPending && " · Loading..."}
        </p>
        <div className="flex gap-2">
          <Button
            variant="outline"
            size="sm"
            disabled={data.page <= 1 || isPending}
            onClick={() => updateParams({ page: String(data.page - 1) })}
          >
            Previous
          </Button>
          <Button
            variant="outline"
            size="sm"
            disabled={data.page >= data.totalPages || isPending}
            onClick={() => updateParams({ page: String(data.page + 1) })}
          >
            Next
          </Button>
        </div>
      </div>
    </div>
  );
}
