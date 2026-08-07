export type NotificationChannel = "IN_APP" | "EMAIL" | "SMS" | "WHATSAPP";

export type NotificationType =
  | "BOOKING_CREATED"
  | "BOOKING_CONFIRMED"
  | "BOOKING_ASSIGNED"
  | "BOOKING_STARTED"
  | "BOOKING_COMPLETED"
  | "BOOKING_CANCELLED"
  | "PAYMENT_RECEIVED"
  | "INVOICE_GENERATED"
  | "REVIEW_REQUEST"
  | "GENERAL";

export interface NotificationPayload {
  userId: string;
  title: string;
  body: string;
  type: NotificationType;
  metadata?: Record<string, unknown>;
  /** Optional contact overrides for external channels */
  email?: string | null;
  phone?: string | null;
}

export interface ChannelSendResult {
  channel: NotificationChannel;
  success: boolean;
  skipped?: boolean;
  error?: string;
}

export interface NotificationChannelProvider {
  channel: NotificationChannel;
  send(payload: NotificationPayload): Promise<ChannelSendResult>;
}
