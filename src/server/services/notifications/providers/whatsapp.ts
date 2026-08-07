import type {
  ChannelSendResult,
  NotificationChannelProvider,
  NotificationPayload,
} from "../types";

/**
 * WhatsApp provider — future Meta Cloud API integration stub.
 */
export const whatsappProvider: NotificationChannelProvider = {
  channel: "WHATSAPP",
  async send(payload: NotificationPayload): Promise<ChannelSendResult> {
    if (!process.env.WHATSAPP_API_TOKEN) {
      console.info(
        `[whatsapp:skipped] to=${payload.phone ?? "n/a"} title="${payload.title}"`
      );
      return { channel: "WHATSAPP", success: true, skipped: true };
    }

    // Future: WhatsApp Cloud API
    console.info(
      `[whatsapp:ready-but-not-implemented] to=${payload.phone} body="${payload.body}"`
    );
    return { channel: "WHATSAPP", success: true, skipped: true };
  },
};
