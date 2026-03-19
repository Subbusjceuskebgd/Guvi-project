-- ============================================================
--  GUVI Internship Project — MySQL Setup Script
--  Run this file once to create the database and tables.
--  Command: mysql -u root -p < setup.sql
-- ============================================================

-- Create database
CREATE DATABASE IF NOT EXISTS guvi_auth
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE guvi_auth;

-- ── Users table (registration data) ──────────────────────
CREATE TABLE IF NOT EXISTS users (
    id          INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    first_name  VARCHAR(50)      NOT NULL,
    last_name   VARCHAR(50)      NOT NULL,
    email       VARCHAR(180)     NOT NULL,
    username    VARCHAR(30)      NOT NULL,
    password    VARCHAR(255)     NOT NULL,   -- bcrypt hash
    created_at  DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_email    (email),
    UNIQUE KEY uq_username (username),
    INDEX       idx_email    (email),
    INDEX       idx_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Verify ───────────────────────────────────────────────
SELECT 'Database and tables created successfully!' AS status;
DESCRIBE users;