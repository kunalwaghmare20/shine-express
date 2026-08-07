import Link from "next/link";
import { Button } from "@/components/ui/button";
import { siteConfig } from "@/config/site";

export default function LandingPage() {
  return (
    <div className="flex flex-1 flex-col">
      <header className="border-b">
        <div className="mx-auto flex h-16 max-w-6xl items-center justify-between px-4">
          <div className="flex items-center gap-2 font-semibold">
            <div className="flex size-8 items-center justify-center rounded-lg bg-primary text-primary-foreground text-sm font-bold">
              SE
            </div>
            {siteConfig.name}
          </div>
          <div className="flex items-center gap-2">
            <Button variant="ghost" asChild>
              <Link href="/login">Sign in</Link>
            </Button>
            <Button asChild>
              <Link href="/register">Get started</Link>
            </Button>
          </div>
        </div>
      </header>

      <main className="mx-auto flex max-w-6xl flex-1 flex-col justify-center gap-8 px-4 py-16">
        <div className="max-w-2xl space-y-4">
          <h1 className="text-4xl font-bold tracking-tight sm:text-5xl">
            Multi-service business management, simplified.
          </h1>
          <p className="text-lg text-muted-foreground">
            House cleaning, car cleaning, pest control, deep cleaning — manage
            bookings, staff, payments, and reports from one platform.
          </p>
          <div className="flex gap-3">
            <Button size="lg" asChild>
              <Link href="/register">Book a service</Link>
            </Button>
            <Button size="lg" variant="outline" asChild>
              <Link href="/login">Admin login</Link>
            </Button>
          </div>
        </div>
      </main>
    </div>
  );
}
