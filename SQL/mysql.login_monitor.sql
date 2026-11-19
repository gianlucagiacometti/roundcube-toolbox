CREATE TABLE `toolbox_login_monitor_devices` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `token_digest` CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    `first_seen_at` BIGINT UNSIGNED NOT NULL,
    `last_seen_at` BIGINT UNSIGNED NOT NULL,
    `device_type` VARCHAR(32) NULL,
    `os_name` VARCHAR(100) NULL,
    `browser_name` VARCHAR(100) NULL,
    `created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `modified` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `modified_by` VARCHAR(255) NOT NULL DEFAULT 'login_monitor',
    UNIQUE KEY `ux_tbx_lm_devices_user_token` (`user_id`, `token_digest`),
    KEY `ix_tbx_lm_devices_user_seen` (`user_id`, `last_seen_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `toolbox_login_monitor_logins` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `device_id` BIGINT UNSIGNED NULL,
    `logged_in_at` BIGINT UNSIGNED NOT NULL,
    `device_type` VARCHAR(32) NULL,
    `os_name` VARCHAR(100) NULL,
    `os_version` VARCHAR(50) NULL,
    `device_brand` VARCHAR(100) NULL,
    `device_model` VARCHAR(150) NULL,
    `city` VARCHAR(150) NULL,
    `country` VARCHAR(100) NULL,
    `ip_address` VARCHAR(45) NULL,
    `user_agent` TEXT NULL,
    `browser_name` VARCHAR(100) NULL,
    `browser_version` VARCHAR(50) NULL,
    `browser_engine` VARCHAR(100) NULL,
    `bot_detection` TINYINT UNSIGNED NULL,
    `created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `ix_tbx_lm_logins_user_time` (`user_id`, `logged_in_at`),
    KEY `ix_tbx_lm_logins_device` (`device_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
