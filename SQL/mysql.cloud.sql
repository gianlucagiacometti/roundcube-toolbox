CREATE TABLE `toolbox_cloud_vaults` (
    `user_id` INT UNSIGNED NOT NULL PRIMARY KEY,
    `password_salt` VARCHAR(64) COLLATE=utf8mb4_0900_ai_ci NULL,
    `password_ops` INT UNSIGNED NULL,
    `password_memory` BIGINT UNSIGNED NULL,
    `password_wrap` TEXT NULL COLLATE=utf8mb4_0900_ai_ci,
    `recovery_wrap` TEXT NULL COLLATE=utf8mb4_0900_ai_ci,
    `key_check` TEXT NOT NULL COLLATE=utf8mb4_0900_ai_ci,
    `created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `modified` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


CREATE TABLE `toolbox_cloud_accounts` (
    `id` BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `crypto_id` CHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL UNIQUE,
    `user_id` INT UNSIGNED NOT NULL,
    `provider` VARCHAR(32) NOT NULL COLLATE=utf8mb4_0900_ai_ci,
    `display_name` VARCHAR(255) NOT NULL COLLATE=utf8mb4_0900_ai_ci,
    `endpoint` VARCHAR(2048) NULL COLLATE=utf8mb4_0900_ai_ci,
    `credentials` MEDIUMTEXT NOT NULL COLLATE=utf8mb4_0900_ai_ci,
    `expires` BIGINT NULL,
    `created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `modified` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_tbx_cloud_accounts_user` (`user_id`, `display_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
