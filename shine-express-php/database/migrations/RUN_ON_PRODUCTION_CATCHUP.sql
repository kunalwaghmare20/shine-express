-- Production catch-up: run in phpMyAdmin if you see errors about
-- whatsapp_reminder_sent_at, reminder_days, or requires_followup.
-- Safe to re-run: skip any statement that says "Duplicate column name".

-- ========== 004_whatsapp_reminders.sql ==========
ALTER TABLE bookings
  ADD COLUMN whatsapp_reminder_sent_at DATETIME(3) NULL AFTER assigned_at;

ALTER TABLE bookings
  ADD INDEX idx_bookings_reminder (scheduled_date, status, whatsapp_reminder_sent_at);

CREATE TABLE IF NOT EXISTS whatsapp_logs (
  id CHAR(24) PRIMARY KEY,
  booking_id CHAR(24) NULL,
  phone VARCHAR(20) NOT NULL,
  message TEXT NOT NULL,
  provider VARCHAR(40) NOT NULL DEFAULT 'log',
  status VARCHAR(20) NOT NULL DEFAULT 'PENDING',
  response_body TEXT NULL,
  created_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  INDEX idx_wa_booking (booking_id),
  CONSTRAINT fk_wa_booking FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========== 005_service_reminder_days.sql ==========
ALTER TABLE services
  ADD COLUMN reminder_days INT NOT NULL DEFAULT 30 AFTER duration;

ALTER TABLE services
  ADD INDEX idx_services_reminder (reminder_days);

UPDATE services SET reminder_days = 30 WHERE reminder_days IS NULL OR reminder_days = 0;

-- ========== 006_booking_followup.sql ==========
ALTER TABLE bookings
  ADD COLUMN requires_followup TINYINT(1) NOT NULL DEFAULT 0 AFTER cancellation_reason;

ALTER TABLE bookings
  ADD INDEX idx_bookings_followup (requires_followup);

-- ========== 007_whatsapp_broadcast_templates.sql ==========
CREATE TABLE IF NOT EXISTS whatsapp_broadcast_templates (
  id VARCHAR(36) NOT NULL PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  body TEXT NOT NULL,
  created_by VARCHAR(36) NULL,
  created_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  updated_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
  INDEX idx_wa_templates_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========== 008_push_broadcast_templates.sql ==========
CREATE TABLE IF NOT EXISTS push_broadcast_templates (
  id VARCHAR(36) NOT NULL PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  title VARCHAR(200) NOT NULL,
  body TEXT NOT NULL,
  created_by VARCHAR(36) NULL,
  created_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  updated_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
  INDEX idx_push_templates_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========== 009_app_settings.sql ==========
CREATE TABLE IF NOT EXISTS app_settings (
  setting_key VARCHAR(80) NOT NULL PRIMARY KEY,
  setting_value TEXT NULL,
  updated_by VARCHAR(36) NULL,
  updated_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
