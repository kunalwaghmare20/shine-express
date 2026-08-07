import type {
  ChannelSendResult,
  NotificationChannelProvider,
  NotificationPayload,
} from "../types";

/**
 * In-app channel — persistence is handled by notification.service.
 * This provider marks the channel as delivered for orchestration.
 */
export const inAppProvider: NotificationChannelProvider = {
  channel: "IN_APP",
  async send(_payload: NotificationPayload): Promise<ChannelSendResult> {
    return { channel: "IN_APP", success: true };
  },
};
