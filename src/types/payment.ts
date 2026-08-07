export enum PaymentMethod {
  CASH = "CASH",
  UPI = "UPI",
  CARD = "CARD",
  RAZORPAY = "RAZORPAY",
  STRIPE = "STRIPE",
}

export enum PaymentStatus {
  PENDING = "PENDING",
  COMPLETED = "COMPLETED",
  FAILED = "FAILED",
  REFUNDED = "REFUNDED",
}

export const PAYMENT_METHOD_LABELS: Record<PaymentMethod, string> = {
  [PaymentMethod.CASH]: "Cash",
  [PaymentMethod.UPI]: "UPI",
  [PaymentMethod.CARD]: "Card",
  [PaymentMethod.RAZORPAY]: "Razorpay",
  [PaymentMethod.STRIPE]: "Stripe",
};
