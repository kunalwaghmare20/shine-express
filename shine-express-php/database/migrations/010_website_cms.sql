-- Website CMS + service flags for the public marketing site
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
