-- Create a DB for Matchy Matchy (change name if you already have one)
CREATE DATABASE IF NOT EXISTS matchy_matchy
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE matchy_matchy;

-- Users table (compatible with local signup + Google later)
CREATE TABLE IF NOT EXISTS users (
                                     id                     INT AUTO_INCREMENT PRIMARY KEY,
                                     first_name             VARCHAR(50)  NOT NULL,
    last_name              VARCHAR(50)  NOT NULL,
  --  username               VARCHAR(50)  NOT NULL UNIQUE,
    email                  VARCHAR(255) NOT NULL UNIQUE,
    password               VARCHAR(255) NULL,         -- stores hash for local signups; NULL for social
    avatar                 VARCHAR(255) NULL,
    provider               ENUM('local','google') NOT NULL DEFAULT 'local',
    google_id              VARCHAR(64)  NULL UNIQUE,
    email_verified_at      DATETIME NULL,
    created_at             TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;


USE matchy_matchy;

ALTER TABLE users
    ADD UNIQUE KEY uniq_email (email),
    ADD UNIQUE KEY uniq_google_id (google_id);
-- Holds 6-digit verification codes (hashed)


CREATE TABLE IF NOT EXISTS email_verifications (
                                                   id             INT AUTO_INCREMENT PRIMARY KEY,
                                                   email          VARCHAR(255) NOT NULL UNIQUE,
    code_hash      VARCHAR(255) NOT NULL,  -- store a *hashed* code
    token          CHAR(64)     NOT NULL,  -- server token you’ll give the browser after verify
    expires_at     DATETIME     NOT NULL,  -- code valid window (e.g., 10 min)
    verified_at    DATETIME     NULL,
    send_count     INT          NOT NULL DEFAULT 1,
    attempt_count  INT          NOT NULL DEFAULT 0,
    last_sent_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;



CREATE TABLE IF NOT EXISTS magic_links (
                                           id          INT AUTO_INCREMENT PRIMARY KEY,
                                           user_id     INT NOT NULL,
                                           token       CHAR(64) NOT NULL UNIQUE,
    purpose     ENUM('login','welcome','set_password') NOT NULL DEFAULT 'set_password',
    expires_at  DATETIME NOT NULL,
    used_at     DATETIME NULL,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_magic_links_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE CASCADE
    ) ENGINE=InnoDB;



-- One-time migration
CREATE TABLE IF NOT EXISTS pending_google_signups (
                                                      id              INT AUTO_INCREMENT PRIMARY KEY,
                                                      google_id       VARCHAR(64)  NOT NULL UNIQUE,
    email           VARCHAR(255) NOT NULL UNIQUE,
    first_name      VARCHAR(50)  NOT NULL,
    last_name       VARCHAR(50)  NOT NULL,
    avatar          VARCHAR(255) NULL,
    email_verified  TINYINT(1)   NOT NULL DEFAULT 0,
    token_hash      CHAR(64)     NOT NULL,         -- SHA-256 of opaque token
    expires_at      DATETIME     NOT NULL,
    created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;


CREATE TABLE IF NOT EXISTS login_attempts (
                                              email           VARCHAR(255) PRIMARY KEY,
    fail_count      INT NOT NULL DEFAULT 0,
    locked_until    DATETIME NULL,
    last_failed_at  TIMESTAMP NULL DEFAULT NULL
    ) ENGINE=InnoDB;


ALTER TABLE magic_links
    MODIFY purpose ENUM('login','welcome','set_password','reset_password')
    NOT NULL DEFAULT 'set_password';
