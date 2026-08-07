import { afterEach, describe, expect, it, vi } from "vitest";
import { emailProvider } from "@/server/services/notifications/providers/email";
import { smsProvider } from "@/server/services/notifications/providers/sms";
import { whatsappProvider } from "@/server/services/notifications/providers/whatsapp";
import type { NotificationPayload } from "@/server/services/notifications/types";

const basePayload: NotificationPayload = {
  userId: "user_1",
  title: "Booking confirmed",
  body: "Your booking SE-001 is confirmed.",
  type: "BOOKING_CONFIRMED",
  email: "customer@example.com",
  phone: "+919876543210",
};

describe("notification channel providers", () => {
  afterEach(() => {
    vi.unstubAllEnvs();
    vi.restoreAllMocks();
  });

  it("skips email when RESEND_API_KEY is missing", async () => {
    vi.stubEnv("RESEND_API_KEY", "");
    const result = await emailProvider.send(basePayload);
    expect(result).toMatchObject({
      channel: "EMAIL",
      success: true,
      skipped: true,
    });
  });

  it("skips email when recipient email is missing", async () => {
    vi.stubEnv("RESEND_API_KEY", "re_test");
    const result = await emailProvider.send({ ...basePayload, email: null });
    expect(result.skipped).toBe(true);
  });

  it("posts to Resend when configured", async () => {
    vi.stubEnv("RESEND_API_KEY", "re_test");
    vi.stubEnv("EMAIL_FROM", "noreply@shineexpress.com");

    const fetchMock = vi.fn().mockResolvedValue({
      ok: true,
      text: async () => "",
    });
    vi.stubGlobal("fetch", fetchMock);

    const result = await emailProvider.send(basePayload);
    expect(result).toEqual({ channel: "EMAIL", success: true });
    expect(fetchMock).toHaveBeenCalledWith(
      "https://api.resend.com/emails",
      expect.objectContaining({
        method: "POST",
        headers: expect.objectContaining({
          Authorization: "Bearer re_test",
        }),
      })
    );
  });

  it("skips SMS and WhatsApp stubs without credentials", async () => {
    vi.stubEnv("TWILIO_ACCOUNT_SID", "");
    vi.stubEnv("TWILIO_AUTH_TOKEN", "");
    vi.stubEnv("WHATSAPP_API_TOKEN", "");

    await expect(smsProvider.send(basePayload)).resolves.toMatchObject({
      channel: "SMS",
      skipped: true,
    });
    await expect(whatsappProvider.send(basePayload)).resolves.toMatchObject({
      channel: "WHATSAPP",
      skipped: true,
    });
  });
});
