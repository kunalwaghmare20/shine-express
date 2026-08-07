-- WhatsApp before-service reminders
ALTER TABLE bookings
  ADD COLUMN whatsapp_reminder_sent_at DATETIME(3) NULL AFTER assigned_at,
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
