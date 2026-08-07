import { clerkMiddleware, createRouteMatcher } from "@clerk/nextjs/server";
import { NextResponse } from "next/server";
import { UserRole } from "@/config/roles";
import { getDefaultRouteForRole, parseUserRole } from "@/lib/auth/sync-user";

const isPublicRoute = createRouteMatcher([
  "/",
  "/login(.*)",
  "/register(.*)",
  "/forgot-password(.*)",
  "/api/webhooks(.*)",
  "/api/health",
]);

const isAdminRoute = createRouteMatcher(["/admin(.*)"]);
const isBranchManagerRoute = createRouteMatcher(["/branch-manager(.*)"]);
const isStaffRoute = createRouteMatcher(["/staff(.*)"]);
const isCustomerRoute = createRouteMatcher([
  "/book(.*)",
  "/bookings(.*)",
  "/history(.*)",
  "/invoices(.*)",
  "/profile(.*)",
  "/notifications(.*)",
]);

function extractRole(sessionClaims: unknown): UserRole {
  const claims = sessionClaims as {
    publicMetadata?: { role?: unknown };
    metadata?: { role?: unknown };
  } | null;

  return parseUserRole(
    claims?.publicMetadata?.role ?? claims?.metadata?.role
  );
}

export default clerkMiddleware(async (auth, request) => {
  if (isPublicRoute(request)) {
    return NextResponse.next();
  }

  const { userId, sessionClaims } = await auth();

  if (!userId) {
    return NextResponse.redirect(new URL("/login", request.url));
  }

  const role = extractRole(sessionClaims);
  const pathname = request.nextUrl.pathname;

  if (isAdminRoute(request) && role !== UserRole.SUPER_ADMIN) {
    return NextResponse.redirect(
      new URL(getDefaultRouteForRole(role), request.url)
    );
  }

  if (isBranchManagerRoute(request) && role !== UserRole.BRANCH_MANAGER) {
    return NextResponse.redirect(
      new URL(getDefaultRouteForRole(role), request.url)
    );
  }

  if (isStaffRoute(request) && role !== UserRole.SERVICE_STAFF) {
    return NextResponse.redirect(
      new URL(getDefaultRouteForRole(role), request.url)
    );
  }

  if (isCustomerRoute(request) && role !== UserRole.CUSTOMER) {
    return NextResponse.redirect(
      new URL(getDefaultRouteForRole(role), request.url)
    );
  }

  return NextResponse.next();
});

export const config = {
  matcher: [
    "/((?!_next|[^?]*\\.(?:html?|css|js(?!on)|jpe?g|webp|png|gif|svg|ttf|woff2?|ico|csv|docx?|xlsx?|zip|webmanifest)).*)",
    "/(api|trpc)(.*)",
  ],
};
