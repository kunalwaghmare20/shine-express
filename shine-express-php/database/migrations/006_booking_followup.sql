-- Low-rating escalation flag (Phase 3)
ALTER TABLE bookings
  ADD COLUMN requires_followup TINYINT(1) NOT NULL DEFAULT 0 AFTER cancellation_reason,
  ADD INDEX idx_bookings_followup (requires_followup);
