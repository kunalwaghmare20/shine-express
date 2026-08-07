"use client";

import { useMemo, useState } from "react";
import { useRouter } from "next/navigation";
import { toast } from "sonner";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Badge } from "@/components/ui/badge";
import type { BookingCatalogService } from "@/server/dto/booking.dto";
import type { CustomerAddressDto } from "@/server/dto/customer.dto";
import type { BranchOption } from "@/server/dto/employee.dto";
import { formatCurrency } from "@/lib/utils/format";

const TIME_SLOTS = [
  "09:00",
  "10:00",
  "11:00",
  "12:00",
  "14:00",
  "15:00",
  "16:00",
  "17:00",
];

interface BookServiceWizardProps {
  catalog: BookingCatalogService[];
  addresses: CustomerAddressDto[];
  branches: BranchOption[];
}

export function BookServiceWizard({
  catalog,
  addresses,
  branches,
}: BookServiceWizardProps) {
  const router = useRouter();
  const [step, setStep] = useState(1);
  const [serviceId, setServiceId] = useState("");
  const [itemIds, setItemIds] = useState<string[]>([]);
  const [scheduledDate, setScheduledDate] = useState("");
  const [scheduledTime, setScheduledTime] = useState("");
  const [addressId, setAddressId] = useState(addresses.find((a) => a.isDefault)?.id ?? addresses[0]?.id ?? "");
  const [branchId, setBranchId] = useState(branches[0]?.id ?? "");
  const [notes, setNotes] = useState("");
  const [submitting, setSubmitting] = useState(false);

  const selectedService = useMemo(
    () => catalog.find((s) => s.id === serviceId),
    [catalog, serviceId]
  );

  const estimate = useMemo(() => {
    if (!selectedService) return { subtotal: 0, tax: 0, total: 0, duration: 0 };
    const lines =
      itemIds.length > 0
        ? selectedService.items.filter((i) => itemIds.includes(i.id))
        : [{ price: selectedService.basePrice, duration: selectedService.duration }];
    const subtotal = lines.reduce((s, i) => s + i.price, 0);
    const tax = Math.round(subtotal * 0.18 * 100) / 100;
    const duration = lines.reduce(
      (s, i) => s + (i.duration ?? 0),
      0
    ) || selectedService.duration;
    return { subtotal, tax, total: subtotal + tax, duration };
  }, [selectedService, itemIds]);

  function toggleItem(id: string) {
    setItemIds((prev) =>
      prev.includes(id) ? prev.filter((x) => x !== id) : [...prev, id]
    );
  }

  async function submit() {
    if (!serviceId || !scheduledDate || !scheduledTime || !addressId || !branchId) {
      toast.error("Please complete all required fields");
      return;
    }

    setSubmitting(true);
    try {
      const res = await fetch("/api/bookings", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          serviceId,
          addressId,
          branchId,
          scheduledDate,
          scheduledTime,
          customerNotes: notes || null,
          serviceItemIds: itemIds,
        }),
      });
      const json = await res.json();
      if (!res.ok) throw new Error(json.message ?? "Booking failed");

      toast.success("Booking created successfully");
      router.push(`/bookings/${json.data.id}`);
      router.refresh();
    } catch (error) {
      toast.error(error instanceof Error ? error.message : "Something went wrong");
    } finally {
      setSubmitting(false);
    }
  }

  if (catalog.length === 0) {
    return (
      <Card>
        <CardContent className="py-10 text-center text-muted-foreground">
          No services available for booking yet.
        </CardContent>
      </Card>
    );
  }

  if (addresses.length === 0) {
    return (
      <Card>
        <CardContent className="space-y-3 py-10 text-center">
          <p className="text-muted-foreground">
            Add an address in your profile before booking.
          </p>
          <Button asChild variant="outline">
            <a href="/profile">Go to Profile</a>
          </Button>
        </CardContent>
      </Card>
    );
  }

  return (
    <div className="mx-auto max-w-2xl space-y-6">
      <div className="flex gap-2">
        {[1, 2, 3, 4].map((s) => (
          <div
            key={s}
            className={`h-1.5 flex-1 rounded-full ${
              s <= step ? "bg-primary" : "bg-muted"
            }`}
          />
        ))}
      </div>

      {step === 1 && (
        <Card>
          <CardHeader>
            <CardTitle>Select Service</CardTitle>
          </CardHeader>
          <CardContent className="space-y-3">
            {catalog.map((service) => (
              <button
                key={service.id}
                type="button"
                onClick={() => {
                  setServiceId(service.id);
                  setItemIds([]);
                }}
                className={`w-full rounded-lg border p-4 text-left transition-colors ${
                  serviceId === service.id
                    ? "border-primary bg-primary/5"
                    : "hover:bg-accent"
                }`}
              >
                <div className="flex items-start justify-between gap-3">
                  <div>
                    <p className="font-medium">{service.name}</p>
                    <p className="text-xs text-muted-foreground">
                      {service.categoryName} · {service.duration} min
                    </p>
                  </div>
                  <Badge variant="secondary">
                    {formatCurrency(service.basePrice)}
                  </Badge>
                </div>
              </button>
            ))}

            {selectedService && selectedService.items.length > 0 && (
              <div className="space-y-2 border-t pt-4">
                <Label>Optional add-ons / rooms</Label>
                {selectedService.items.map((item) => (
                  <label
                    key={item.id}
                    className="flex cursor-pointer items-center justify-between rounded-lg border p-3 text-sm"
                  >
                    <span className="flex items-center gap-2">
                      <input
                        type="checkbox"
                        checked={itemIds.includes(item.id)}
                        onChange={() => toggleItem(item.id)}
                        className="rounded"
                      />
                      {item.name}
                    </span>
                    <span>{formatCurrency(item.price)}</span>
                  </label>
                ))}
              </div>
            )}

            <Button
              className="w-full"
              disabled={!serviceId}
              onClick={() => setStep(2)}
            >
              Continue
            </Button>
          </CardContent>
        </Card>
      )}

      {step === 2 && (
        <Card>
          <CardHeader>
            <CardTitle>Select Date & Time</CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="space-y-2">
              <Label htmlFor="date">Date</Label>
              <Input
                id="date"
                type="date"
                min={new Date().toISOString().slice(0, 10)}
                value={scheduledDate}
                onChange={(e) => setScheduledDate(e.target.value)}
              />
            </div>
            <div className="space-y-2">
              <Label>Time slot</Label>
              <div className="grid grid-cols-4 gap-2">
                {TIME_SLOTS.map((slot) => (
                  <Button
                    key={slot}
                    type="button"
                    variant={scheduledTime === slot ? "default" : "outline"}
                    size="sm"
                    onClick={() => setScheduledTime(slot)}
                  >
                    {slot}
                  </Button>
                ))}
              </div>
            </div>
            <div className="flex gap-2">
              <Button variant="outline" onClick={() => setStep(1)}>
                Back
              </Button>
              <Button
                className="flex-1"
                disabled={!scheduledDate || !scheduledTime}
                onClick={() => setStep(3)}
              >
                Continue
              </Button>
            </div>
          </CardContent>
        </Card>
      )}

      {step === 3 && (
        <Card>
          <CardHeader>
            <CardTitle>Address & Branch</CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="space-y-2">
              <Label>Service address</Label>
              <div className="space-y-2">
                {addresses.map((addr) => (
                  <button
                    key={addr.id}
                    type="button"
                    onClick={() => setAddressId(addr.id)}
                    className={`w-full rounded-lg border p-3 text-left text-sm ${
                      addressId === addr.id
                        ? "border-primary bg-primary/5"
                        : "hover:bg-accent"
                    }`}
                  >
                    <p className="font-medium">{addr.label}</p>
                    <p className="text-muted-foreground">
                      {addr.line1}, {addr.city} {addr.pincode}
                    </p>
                  </button>
                ))}
              </div>
            </div>
            <div className="space-y-2">
              <Label htmlFor="branch">Branch</Label>
              <select
                id="branch"
                className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm"
                value={branchId}
                onChange={(e) => setBranchId(e.target.value)}
              >
                {branches.map((b) => (
                  <option key={b.id} value={b.id}>
                    {b.name} ({b.city})
                  </option>
                ))}
              </select>
            </div>
            <div className="space-y-2">
              <Label htmlFor="notes">Notes (optional)</Label>
              <Textarea
                id="notes"
                rows={3}
                value={notes}
                onChange={(e) => setNotes(e.target.value)}
                placeholder="Gate code, parking instructions..."
              />
            </div>
            <div className="flex gap-2">
              <Button variant="outline" onClick={() => setStep(2)}>
                Back
              </Button>
              <Button
                className="flex-1"
                disabled={!addressId || !branchId}
                onClick={() => setStep(4)}
              >
                Continue
              </Button>
            </div>
          </CardContent>
        </Card>
      )}

      {step === 4 && (
        <Card>
          <CardHeader>
            <CardTitle>Confirm Booking</CardTitle>
          </CardHeader>
          <CardContent className="space-y-4 text-sm">
            <div className="space-y-2 rounded-lg border p-4">
              <p>
                <span className="text-muted-foreground">Service:</span>{" "}
                {selectedService?.name}
              </p>
              <p>
                <span className="text-muted-foreground">When:</span>{" "}
                {scheduledDate} at {scheduledTime}
              </p>
              <p>
                <span className="text-muted-foreground">Duration:</span>{" "}
                ~{estimate.duration} min
              </p>
              <p>
                <span className="text-muted-foreground">Subtotal:</span>{" "}
                {formatCurrency(estimate.subtotal)}
              </p>
              <p>
                <span className="text-muted-foreground">GST (18%):</span>{" "}
                {formatCurrency(estimate.tax)}
              </p>
              <p className="text-lg font-semibold">
                Total: {formatCurrency(estimate.total)}
              </p>
              <p className="text-xs text-muted-foreground">
                Payment: Cash on service completion
              </p>
            </div>
            <div className="flex gap-2">
              <Button variant="outline" onClick={() => setStep(3)}>
                Back
              </Button>
              <Button className="flex-1" disabled={submitting} onClick={submit}>
                {submitting ? "Creating..." : "Confirm Booking"}
              </Button>
            </div>
          </CardContent>
        </Card>
      )}
    </div>
  );
}
