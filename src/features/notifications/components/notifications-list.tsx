"use client";

import { useCallback, useEffect, useState } from "react";
import { toast } from "sonner";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import { Skeleton } from "@/components/ui/skeleton";
import { cn } from "@/lib/utils";

interface NotificationItem {
  id: string;
  title: string;
  body: string;
  type: string;
  isRead: boolean;
  createdAt: string;
}

export function NotificationsList() {
  const [items, setItems] = useState<NotificationItem[]>([]);
  const [loading, setLoading] = useState(true);
  const [unreadOnly, setUnreadOnly] = useState(false);

  const load = useCallback(async () => {
    setLoading(true);
    try {
      const qs = new URLSearchParams({ limit: "50" });
      if (unreadOnly) qs.set("unreadOnly", "true");
      const res = await fetch(`/api/notifications?${qs}`);
      if (!res.ok) throw new Error("Failed to load");
      const json = await res.json();
      setItems(json.data ?? []);
    } catch {
      toast.error("Could not load notifications");
    } finally {
      setLoading(false);
    }
  }, [unreadOnly]);

  useEffect(() => {
    void load();
  }, [load]);

  async function markRead(id: string) {
    setItems((prev) =>
      prev.map((n) => (n.id === id ? { ...n, isRead: true } : n))
    );
    await fetch(`/api/notifications/${id}/read`, { method: "PATCH" });
  }

  async function markAllRead() {
    await fetch("/api/notifications/read-all", { method: "PATCH" });
    toast.success("All notifications marked as read");
    void load();
  }

  return (
    <div className="space-y-4">
      <div className="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h2 className="text-2xl font-semibold tracking-tight">Notifications</h2>
          <p className="text-sm text-muted-foreground">
            Booking updates and job alerts
          </p>
        </div>
        <div className="flex gap-2">
          <Button
            variant={unreadOnly ? "default" : "outline"}
            size="sm"
            onClick={() => setUnreadOnly((v) => !v)}
          >
            {unreadOnly ? "Showing unread" : "Unread only"}
          </Button>
          <Button variant="outline" size="sm" onClick={() => void markAllRead()}>
            Mark all read
          </Button>
        </div>
      </div>

      {loading ? (
        <div className="space-y-3">
          {Array.from({ length: 4 }).map((_, i) => (
            <Card key={i}>
              <CardHeader>
                <Skeleton className="h-4 w-40" />
                <Skeleton className="h-3 w-64" />
              </CardHeader>
            </Card>
          ))}
        </div>
      ) : items.length === 0 ? (
        <Card>
          <CardContent className="py-10 text-center text-sm text-muted-foreground">
            No notifications to show
          </CardContent>
        </Card>
      ) : (
        <div className="space-y-2">
          {items.map((item) => (
            <Card
              key={item.id}
              className={cn(
                "cursor-pointer transition-colors hover:bg-muted/40",
                !item.isRead && "border-primary/30"
              )}
              onClick={() => {
                if (!item.isRead) void markRead(item.id);
              }}
            >
              <CardHeader className="pb-2">
                <div className="flex items-start justify-between gap-3">
                  <CardTitle className="text-base">{item.title}</CardTitle>
                  <div className="flex items-center gap-2">
                    {!item.isRead && <Badge variant="default">New</Badge>}
                    <span className="text-xs text-muted-foreground">
                      {new Date(item.createdAt).toLocaleString()}
                    </span>
                  </div>
                </div>
                <CardDescription className="text-sm text-foreground/80">
                  {item.body}
                </CardDescription>
              </CardHeader>
            </Card>
          ))}
        </div>
      )}
    </div>
  );
}
