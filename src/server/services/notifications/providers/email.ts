import type {
  ChannelSendResult,
  NotificationChannelProvider,
  NotificationPayload,
} from "../types";

/**
 * Email provider — Resend-ready.
 * When RESEND_API_KEY is set, sends via Resend HTTP API.
 * Otherwise logs and marks as skipped (dev-safe).
 */
export const emailProvider: NotificationChannelProvider = {
  channel: "EMAIL",
  async send(payload: NotificationPayload): Promise<ChannelSendResult> {
    const apiKey = process.env.RESEND_API_KEY;
    const from = process.env.EMAIL_FROM ?? "noreply@shineexpress.com";

    if (!payload.email) {
      return {
        channel: "EMAIL",
        success: true,
        skipped: true,
        error: "No email address",
      };
    }

    if (!apiKey) {
      console.info(
        `[email:skipped] to=${payload.email} title="${payload.title}"`
      );
      return { channel: "EMAIL", success: true, skipped: true };
    }

    try {
      const res = await fetch("https://api.resend.com/emails", {
        method: "POST",
        headers: {
          Authorization: `Bearer ${apiKey}`,
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          from,
          to: [payload.email],
          subject: payload.title,
          text: payload.body,
        }),
      });

      if (!res.ok) {
        const text = await res.text();
        return {
          channel: "EMAIL",
          success: false,
          error: text || `HTTP ${res.status}`,
        };
      }

      return { channel: "EMAIL", success: true };
    } catch (error) {
      return {
        channel: "EMAIL",
        success: false,
        error: error instanceof Error ? error.message : "Email send failed",
      };
    }
  },
};
