import { Webhook } from "svix";
import { headers } from "next/headers";
import { WebhookEvent } from "@clerk/nextjs/server";
import { NextResponse } from "next/server";
import {
  syncUserFromClerk,
  deleteUserByClerkId,
  parseUserRole,
} from "@/lib/auth";

export async function POST(req: Request) {
  const webhookSecret = process.env.CLERK_WEBHOOK_SECRET;

  if (!webhookSecret) {
    console.error("CLERK_WEBHOOK_SECRET is not configured");
    return NextResponse.json(
      { error: "Webhook secret not configured" },
      { status: 500 }
    );
  }

  const headerPayload = await headers();
  const svixId = headerPayload.get("svix-id");
  const svixTimestamp = headerPayload.get("svix-timestamp");
  const svixSignature = headerPayload.get("svix-signature");

  if (!svixId || !svixTimestamp || !svixSignature) {
    return NextResponse.json({ error: "Missing svix headers" }, { status: 400 });
  }

  const payload = await req.json();
  const body = JSON.stringify(payload);
  const wh = new Webhook(webhookSecret);

  let event: WebhookEvent;

  try {
    event = wh.verify(body, {
      "svix-id": svixId,
      "svix-timestamp": svixTimestamp,
      "svix-signature": svixSignature,
    }) as WebhookEvent;
  } catch {
    return NextResponse.json({ error: "Invalid signature" }, { status: 400 });
  }

  try {
    switch (event.type) {
      case "user.created":
      case "user.updated": {
        const { id, email_addresses, first_name, last_name, phone_numbers, image_url, public_metadata } =
          event.data;

        const email = email_addresses[0]?.email_address;
        if (!email) break;

        await syncUserFromClerk({
          clerkId: id,
          email,
          firstName: first_name ?? "User",
          lastName: last_name ?? "",
          phone: phone_numbers[0]?.phone_number,
          avatarUrl: image_url,
          role: parseUserRole(public_metadata?.role),
        });
        break;
      }

      case "user.deleted": {
        if (event.data.id) {
          await deleteUserByClerkId(event.data.id);
        }
        break;
      }

      default:
        break;
    }
  } catch (error) {
    console.error("Clerk webhook handler error:", error);
    return NextResponse.json(
      { error: "Webhook handler failed" },
      { status: 500 }
    );
  }

  return NextResponse.json({ received: true });
}
