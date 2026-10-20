CREATE DATABASE IF NOT EXISTS marketing_cms
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;
USE marketing_cms;

CREATE TABLE IF NOT EXISTS roles (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(40) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_roles_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO roles (name) VALUES
    ('superadministrator'),
    ('administrator'),
    ('campaign_manager'),
    ('viewer');

CREATE TABLE IF NOT EXISTS admins (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    role_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(254) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    last_login_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_admins_email (email),
    KEY idx_admins_role_active (role_id, is_active),
    CONSTRAINT fk_admins_role FOREIGN KEY (role_id) REFERENCES roles (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS admin_sessions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    admin_id BIGINT UNSIGNED NOT NULL,
    session_token_hash CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_admin_sessions_token (session_token_hash),
    KEY idx_admin_sessions_expiry (expires_at),
    CONSTRAINT fk_admin_sessions_admin FOREIGN KEY (admin_id) REFERENCES admins (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS admin_activity_logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    admin_id BIGINT UNSIGNED NULL,
    action VARCHAR(100) NOT NULL,
    details JSON NULL,
    ip_address VARCHAR(45) NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_activity_admin_created (admin_id, created_at),
    CONSTRAINT fk_activity_admin FOREIGN KEY (admin_id) REFERENCES admins (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS settings (
    setting_key VARCHAR(120) NOT NULL,
    setting_value TEXT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS email_lists (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(160) NOT NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL,
    PRIMARY KEY (id),
    KEY idx_email_lists_name (name),
    CONSTRAINT fk_email_lists_admin FOREIGN KEY (created_by) REFERENCES admins (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS email_contacts (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    email VARCHAR(254) NOT NULL,
    first_name VARCHAR(120) NULL,
    last_name VARCHAR(120) NULL,
    status ENUM('pending_consent', 'active', 'unsubscribed', 'bounced', 'suppressed') NOT NULL DEFAULT 'pending_consent',
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_email_contacts_email (email),
    KEY idx_email_contacts_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS email_list_contacts (
    list_id BIGINT UNSIGNED NOT NULL,
    contact_id BIGINT UNSIGNED NOT NULL,
    added_at DATETIME NOT NULL,
    PRIMARY KEY (list_id, contact_id),
    KEY idx_list_contacts_contact (contact_id),
    CONSTRAINT fk_list_contacts_list FOREIGN KEY (list_id) REFERENCES email_lists (id) ON DELETE CASCADE,
    CONSTRAINT fk_list_contacts_contact FOREIGN KEY (contact_id) REFERENCES email_contacts (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS contact_consents (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    contact_id BIGINT UNSIGNED NOT NULL,
    legal_basis VARCHAR(80) NOT NULL,
    source VARCHAR(255) NOT NULL,
    evidence_reference VARCHAR(512) NULL,
    granted_at DATETIME NOT NULL,
    withdrawn_at DATETIME NULL,
    PRIMARY KEY (id),
    KEY idx_consents_contact_granted (contact_id, granted_at),
    CONSTRAINT fk_consents_contact FOREIGN KEY (contact_id) REFERENCES email_contacts (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS suppression_list (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    email VARCHAR(254) NOT NULL,
    reason ENUM('unsubscribe', 'hard_bounce', 'complaint', 'manual') NOT NULL,
    source VARCHAR(255) NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_suppression_email (email),
    KEY idx_suppression_reason_created (reason, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
