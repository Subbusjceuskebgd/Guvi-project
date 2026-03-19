-- ============================================================
-- GUVI Internship Project - MySQL Setup
-- Run this once to create the database and users table.
-- ============================================================

CREATE DATABASE IF NOT EXISTS guvi_auth
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE guvi_auth;

CREATE TABLE IF NOT EXISTS users (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100)  NOT NULL,
    email      VARCHAR(180)  NOT NULL UNIQUE,
    username   VARCHAR(30)   NOT NULL UNIQUE,
    password   VARCHAR(255)  NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;