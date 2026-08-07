import type {
  ChannelSendResult,
  NotificationChannelProvider,
  NotificationPayload,
} from "../types";

/**
 * SMS provider — Twilio-ready stub for future integration.
 */
export const smsProvider: NotificationChannelProvider = {
  channel: "SMS",
  async send(payload: NotificationPayload): Promise<ChannelSendResult> {
    if (!process.env.TWILIO_ACCOUNT_SID || !process.env.TWILIO_AUTH_TOKEN) {
      console.info(
        `[sms:skipped] to=${payload.phone ?? "n/a"} title="${payload.title}"`
      );
      return { channel: "SMS", success: true, skipped: true };
    }

    // Future: Twilio Messages API
    console.info(
      `[sms:ready-but-not-implemented] to=${payload.phone} body="${payload.body}"`
    );
    return { channel: "SMS", success: true, skipped: true };
  },
};
