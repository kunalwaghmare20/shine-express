-- API tokens for mobile apps
CREATE TABLE IF NOT EXISTS api_tokens (
  id VARCHAR(36) NOT NULL PRIMARY KEY,
  user_id VARCHAR(36) NOT NULL,
  token VARCHAR(64) NOT NULL UNIQUE,
  device_name VARCHAR(100) NULL,
  last_used_at DATETIME NULL,
  expires_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_api_tokens_user (user_id),
  INDEX idx_api_tokens_token (token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
