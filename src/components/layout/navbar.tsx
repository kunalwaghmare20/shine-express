"use client";

import { UserButton } from "@clerk/nextjs";
import { Menu, Moon, Sun } from "lucide-react";
import { usePathname } from "next/navigation";
import { useTheme } from "next-themes";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { useSidebar } from "@/components/layout/sidebar";
import { NotificationBell } from "@/features/notifications";

interface NavbarProps {
  title?: string;
}

function notificationsHref(pathname: string) {
  if (pathname.startsWith("/admin")) return "/admin/notifications";
  if (pathname.startsWith("/branch-manager")) {
    return "/branch-manager/notifications";
  }
  if (pathname.startsWith("/staff")) return "/staff/notifications";
  return "/notifications";
}

export function Navbar({ title = "Dashboard" }: NavbarProps) {
  const { setCollapsed, collapsed } = useSidebar();
  const { theme, setTheme } = useTheme();
  const pathname = usePathname();

  return (
    <header className="sticky top-0 z-40 flex h-14 items-center gap-4 border-b bg-background/95 px-4 backdrop-blur supports-[backdrop-filter]:bg-background/60 md:px-6">
      <Button
        variant="ghost"
        size="icon"
        className="md:hidden"
        onClick={() => setCollapsed(!collapsed)}
      >
        <Menu className="size-5" />
      </Button>

      <Button
        variant="ghost"
        size="icon"
        className="hidden md:inline-flex"
        onClick={() => setCollapsed(!collapsed)}
      >
        <Menu className="size-5" />
      </Button>

      <h1 className="text-lg font-semibold">{title}</h1>

      <div className="ml-auto flex items-center gap-2">
        <Input
          placeholder="Search..."
          className="hidden w-64 lg:block"
        />

        <NotificationBell viewAllHref={notificationsHref(pathname)} />

        <Button
          variant="ghost"
          size="icon"
          onClick={() => setTheme(theme === "dark" ? "light" : "dark")}
        >
          <Sun className="size-5 rotate-0 scale-100 transition-all dark:-rotate-90 dark:scale-0" />
          <Moon className="absolute size-5 rotate-90 scale-0 transition-all dark:rotate-0 dark:scale-100" />
          <span className="sr-only">Toggle theme</span>
        </Button>

        <UserButton afterSignOutUrl="/login" />
      </div>
    </header>
  );
}
