-- Per-service rebook reminder (days after completed service)
ALTER TABLE services
  ADD COLUMN reminder_days INT NOT NULL DEFAULT 30 AFTER duration,
  ADD INDEX idx_services_reminder (reminder_days);

-- Backfill sensible defaults for existing services
UPDATE services SET reminder_days = 30 WHERE reminder_days IS NULL OR reminder_days = 0;
