-- Production catch-up: run in phpMyAdmin if you see errors about
-- missing columns or tables (reminders, followup, broadcasts, website CMS).
-- Safe to re-run: skip any statement that says "Duplicate column name"
-- or "Duplicate key name".

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

-- ========== 010_website_cms.sql ==========
ALTER TABLE services
  ADD COLUMN is_featured TINYINT(1) NOT NULL DEFAULT 0;

ALTER TABLE services
  ADD COLUMN show_on_website TINYINT(1) NOT NULL DEFAULT 1;

ALTER TABLE services
  ADD COLUMN cover_image VARCHAR(500) NULL;

CREATE TABLE IF NOT EXISTS cms_pages (
  id VARCHAR(36) NOT NULL PRIMARY KEY,
  slug VARCHAR(120) NOT NULL UNIQUE,
  title VARCHAR(180) NOT NULL,
  body TEXT NOT NULL,
  seo_title VARCHAR(180) NULL,
  seo_description VARCHAR(300) NULL,
  updated_by VARCHAR(36) NULL,
  created_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  updated_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cms_gallery (
  id VARCHAR(36) NOT NULL PRIMARY KEY,
  image_path VARCHAR(500) NOT NULL,
  caption VARCHAR(255) NULL,
  sort_order INT NOT NULL DEFAULT 0,
  show_on_home TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  INDEX idx_cms_gallery_sort (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cms_testimonials (
  id VARCHAR(36) NOT NULL PRIMARY KEY,
  quote VARCHAR(800) NOT NULL,
  name VARCHAR(120) NOT NULL,
  area VARCHAR(120) NULL,
  rating TINYINT NOT NULL DEFAULT 5,
  photo_path VARCHAR(500) NULL,
  sort_order INT NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  INDEX idx_cms_testimonials_sort (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS website_enquiries (
  id VARCHAR(36) NOT NULL PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  phone VARCHAR(30) NULL,
  email VARCHAR(255) NULL,
  message TEXT NOT NULL,
  created_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  INDEX idx_website_enquiries_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO cms_pages (id, slug, title, body, seo_title, seo_description) VALUES (
  '00000000-0000-4000-8000-0000000000ab',
  'about',
  'About Shine Express',
  'Shine Express is a boutique home-care studio for people who want hotel-level finish without the noise of a typical cleaning brand.\n\nWe look after sofas, kitchens, bathrooms, and pest care with the same quiet standard: careful work, considered products, and a home that feels settled when we leave.',
  'About Shine Express',
  'The story behind Shine Express — hotel-level home care for sofas, kitchens, bathrooms, and pest control.'
);
