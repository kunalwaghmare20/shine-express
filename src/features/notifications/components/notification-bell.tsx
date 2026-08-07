"use client";

import { useCallback, useEffect, useState } from "react";
import Link from "next/link";
import { Bell } from "lucide-react";
import { Button } from "@/components/ui/button";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import { cn } from "@/lib/utils";

interface NotificationItem {
  id: string;
  title: string;
  body: string;
  type: string;
  isRead: boolean;
  metadata: Record<string, unknown>;
  createdAt: string;
}

interface NotificationBellProps {
  viewAllHref?: string;
}

function timeAgo(iso: string) {
  const diff = Date.now() - new Date(iso).getTime();
  const mins = Math.floor(diff / 60000);
  if (mins < 1) return "just now";
  if (mins < 60) return `${mins}m ago`;
  const hours = Math.floor(mins / 60);
  if (hours < 24) return `${hours}h ago`;
  const days = Math.floor(hours / 24);
  return `${days}d ago`;
}

export function NotificationBell({
  viewAllHref = "/notifications",
}: NotificationBellProps) {
  const [items, setItems] = useState<NotificationItem[]>([]);
  const [unreadCount, setUnreadCount] = useState(0);
  const [open, setOpen] = useState(false);

  const load = useCallback(async () => {
    try {
      const [listRes, countRes] = await Promise.all([
        fetch("/api/notifications?limit=8"),
        fetch("/api/notifications?countOnly=true"),
      ]);
      if (listRes.ok) {
        const json = await listRes.json();
        setItems(json.data ?? []);
      }
      if (countRes.ok) {
        const json = await countRes.json();
        setUnreadCount(json.data?.unreadCount ?? 0);
      }
    } catch {
      // ignore — bell is best-effort
    }
  }, []);

  useEffect(() => {
    void load();
    const id = setInterval(() => void load(), 60_000);
    return () => clearInterval(id);
  }, [load]);

  useEffect(() => {
    if (open) void load();
  }, [open, load]);

  async function markRead(id: string) {
    setItems((prev) =>
      prev.map((n) => (n.id === id ? { ...n, isRead: true } : n))
    );
    setUnreadCount((c) => Math.max(0, c - 1));
    await fetch(`/api/notifications/${id}/read`, { method: "PATCH" });
  }

  async function markAllRead() {
    setItems((prev) => prev.map((n) => ({ ...n, isRead: true })));
    setUnreadCount(0);
    await fetch("/api/notifications/read-all", { method: "PATCH" });
  }

  return (
    <DropdownMenu open={open} onOpenChange={setOpen}>
      <DropdownMenuTrigger asChild>
        <Button variant="ghost" size="icon" className="relative">
          <Bell className="size-5" />
          {unreadCount > 0 && (
            <span className="absolute right-1 top-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-primary px-1 text-[10px] font-medium text-primary-foreground">
              {unreadCount > 9 ? "9+" : unreadCount}
            </span>
          )}
          <span className="sr-only">Notifications</span>
        </Button>
      </DropdownMenuTrigger>
      <DropdownMenuContent align="end" className="w-80 p-0">
        <div className="flex items-center justify-between px-3 py-2">
          <DropdownMenuLabel className="p-0">Notifications</DropdownMenuLabel>
          {unreadCount > 0 && (
            <button
              type="button"
              className="text-xs text-primary hover:underline"
              onClick={() => void markAllRead()}
            >
              Mark all read
            </button>
          )}
        </div>
        <DropdownMenuSeparator className="m-0" />
        {items.length === 0 ? (
          <p className="px-3 py-6 text-center text-sm text-muted-foreground">
            No notifications yet
          </p>
        ) : (
          <div className="max-h-80 overflow-y-auto">
            {items.map((item) => (
              <DropdownMenuItem
                key={item.id}
                className={cn(
                  "flex cursor-pointer flex-col items-start gap-0.5 rounded-none px-3 py-2.5",
                  !item.isRead && "bg-muted/50"
                )}
                onSelect={(e) => {
                  e.preventDefault();
                  if (!item.isRead) void markRead(item.id);
                }}
              >
                <div className="flex w-full items-start justify-between gap-2">
                  <span className="text-sm font-medium leading-snug">
                    {item.title}
                  </span>
                  <span className="shrink-0 text-[10px] text-muted-foreground">
                    {timeAgo(item.createdAt)}
                  </span>
                </div>
                <span className="line-clamp-2 text-xs text-muted-foreground">
                  {item.body}
                </span>
              </DropdownMenuItem>
            ))}
          </div>
        )}
        <DropdownMenuSeparator className="m-0" />
        <div className="p-1">
          <DropdownMenuItem asChild className="justify-center text-sm">
            <Link href={viewAllHref} onClick={() => setOpen(false)}>
              View all
            </Link>
          </DropdownMenuItem>
        </div>
      </DropdownMenuContent>
    </DropdownMenu>
  );
}
