"use client";

import Link from "next/link";
import { useRouter, useSearchParams } from "next/navigation";
import { useCallback, useTransition } from "react";
import { Search } from "lucide-react";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { CreateCustomerDialog } from "@/features/customers/components/create-customer-dialog";
import type { CustomerListItem } from "@/server/dto/customer.dto";
import type { PaginatedResult } from "@/server/dto/pagination.dto";
import { formatCurrency } from "@/lib/utils/format";

interface CustomersTableProps {
  data: PaginatedResult<CustomerListItem>;
  basePath?: string;
  canCreate?: boolean;
}

export function CustomersTable({
  data,
  basePath = "/admin/customers",
  canCreate = true,
}: CustomersTableProps) {
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
            placeholder="Search name, email, phone..."
            className="pl-9"
          />
        </form>
        <CreateCustomerDialog canCreate={canCreate} />
      </div>

      <div className="overflow-hidden rounded-xl border">
        <table className="w-full text-sm">
          <thead className="border-b bg-muted/40">
            <tr className="text-left text-muted-foreground">
              <th className="px-4 py-3 font-medium">Customer</th>
              <th className="px-4 py-3 font-medium">Contact</th>
              <th className="px-4 py-3 font-medium">Bookings</th>
              <th className="px-4 py-3 font-medium">Total Spent</th>
              <th className="px-4 py-3 font-medium">Status</th>
              <th className="px-4 py-3 font-medium" />
            </tr>
          </thead>
          <tbody>
            {data.items.length === 0 ? (
              <tr>
                <td colSpan={6} className="px-4 py-12 text-center text-muted-foreground">
                  No customers found
                </td>
              </tr>
            ) : (
              data.items.map((customer) => (
                <tr key={customer.id} className="border-b last:border-0">
                  <td className="px-4 py-3">
                    <div className="font-medium">
                      {customer.firstName} {customer.lastName}
                    </div>
                    {customer.gstNumber && (
                      <div className="text-xs text-muted-foreground">
                        GST: {customer.gstNumber}
                      </div>
                    )}
                  </td>
                  <td className="px-4 py-3">
                    <div>{customer.email}</div>
                    <div className="text-muted-foreground">{customer.phone}</div>
                  </td>
                  <td className="px-4 py-3">{customer.bookingsCount}</td>
                  <td className="px-4 py-3">
                    {formatCurrency(customer.totalSpent)}
                  </td>
                  <td className="px-4 py-3">
                    <Badge variant={customer.isActive ? "success" : "secondary"}>
                      {customer.isActive ? "Active" : "Inactive"}
                    </Badge>
                  </td>
                  <td className="px-4 py-3 text-right">
                    <Button variant="outline" size="sm" asChild>
                      <Link href={`${basePath}/${customer.id}`}>View</Link>
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
          Page {data.page} of {data.totalPages} · {data.total} customers
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
