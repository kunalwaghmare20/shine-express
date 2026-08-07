export const siteConfig = {
  name: "Shine Express",
  description:
    "Professional multi-service cleaning & maintenance platform — house cleaning, car cleaning, pest control, and more.",
  url: process.env.NEXT_PUBLIC_APP_URL ?? "http://localhost:3000",
  ogImage: "/assets/images/og.png",
  links: {
    support: "mailto:support@shineexpress.com",
  },
} as const;

export type SiteConfig = typeof siteConfig;
