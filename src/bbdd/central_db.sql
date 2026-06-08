-- ============================================================
-- BASE DE DATOS CENTRAL - Mezzix
-- ============================================================
-- Descripción : BD compartida entre todos los sitios/tenants.
--               Contiene usuarios, sitios, roles, licencias y
--               las tablas de infraestructura de Laravel.
-- Conexión    : 'central' (config/database.php)
-- Charset     : utf8mb4 / utf8mb4_unicode_ci
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
-- Crear / seleccionar base de datos
-- ------------------------------------------------------------
CREATE DATABASE IF NOT EXISTS `central_db`
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

USE `central_db`;

-- ============================================================
-- TABLA: migrations
-- Registro interno de migraciones de Laravel
-- ============================================================
CREATE TABLE IF NOT EXISTS `migrations` (
  `id`        INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `migration` VARCHAR(255)     NOT NULL,
  `batch`     INT              NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLA: sitios
-- Registro de cada tenant / sitio del sistema
-- Modelo: App\Models\Site
-- ============================================================
CREATE TABLE IF NOT EXISTS `sitios` (
  `id`                   BIGINT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `nombre`               VARCHAR(255)     NOT NULL,
  `dominio`              VARCHAR(255)     NOT NULL,
  `ruta_logo`            VARCHAR(255)         NULL DEFAULT NULL,
  `ruta_logo_nav`        VARCHAR(255)         NULL DEFAULT NULL,
  `ruta_estilos`         VARCHAR(255)         NULL DEFAULT NULL,
  `db_host`              VARCHAR(255)     NOT NULL DEFAULT '127.0.0.1',
  `db_name`              VARCHAR(255)     NOT NULL,
  `db_user`              VARCHAR(255)     NOT NULL,
  `db_password`          VARCHAR(255)     NOT NULL,
  `central`              TINYINT(1)       NOT NULL DEFAULT 0,
  `favicon`              VARCHAR(255)         NULL DEFAULT NULL,
  -- Configuración de correo por sitio
  `mail_mailer`          VARCHAR(50)          NULL DEFAULT NULL,
  `mail_host`            VARCHAR(255)         NULL DEFAULT NULL,
  `mail_port`            SMALLINT UNSIGNED    NULL DEFAULT NULL,
  `mail_username`        VARCHAR(255)         NULL DEFAULT NULL,
  `mail_password`        VARCHAR(255)         NULL DEFAULT NULL,
  `mail_encryption`      VARCHAR(20)          NULL DEFAULT NULL,
  `mail_from_address`    VARCHAR(255)         NULL DEFAULT NULL,
  `mail_from_name`       VARCHAR(255)         NULL DEFAULT NULL,
  -- Datos del local / negocio
  `locale`               VARCHAR(10)      NOT NULL DEFAULT 'es',
  `direccion`            VARCHAR(255)         NULL DEFAULT NULL,
  `cif`                  VARCHAR(50)          NULL DEFAULT NULL,
  `telefono`             VARCHAR(50)          NULL DEFAULT NULL,
  `carpeta_pwa`          VARCHAR(255)         NULL DEFAULT NULL,
  `created_at`           TIMESTAMP            NULL DEFAULT NULL,
  `updated_at`           TIMESTAMP            NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sitios_dominio_unique` (`dominio`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLA: roles
-- Roles de usuario (globales)
-- Modelo: App\Models\Role
-- ============================================================
CREATE TABLE IF NOT EXISTS `roles` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`       VARCHAR(255)    NOT NULL,
  `guard_name` VARCHAR(255)    NOT NULL DEFAULT 'web',
  `created_at` TIMESTAMP           NULL DEFAULT NULL,
  `updated_at` TIMESTAMP           NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_guard_name_unique` (`name`, `guard_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLA: permissions
-- Permisos del sistema
-- Modelo: App\Models\Permission
-- ============================================================
CREATE TABLE IF NOT EXISTS `permissions` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`       VARCHAR(255)    NOT NULL,
  `guard_name` VARCHAR(255)    NOT NULL DEFAULT 'web',
  `created_at` TIMESTAMP           NULL DEFAULT NULL,
  `updated_at` TIMESTAMP           NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_name_guard_name_unique` (`name`, `guard_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLA: users
-- Usuarios del sistema (multi-tenant)
-- Modelo: App\Models\User
-- ============================================================
CREATE TABLE IF NOT EXISTS `users` (
  `id`                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`              VARCHAR(255)    NOT NULL,
  `email`             VARCHAR(255)    NOT NULL,
  `password`          VARCHAR(255)    NOT NULL,
  `image`             VARCHAR(255)    NOT NULL DEFAULT '',
  `role_id`           INT             NOT NULL DEFAULT 1,
  `phone_number`      VARCHAR(255)    NOT NULL DEFAULT '',
  `site_id`           BIGINT UNSIGNED     NULL DEFAULT NULL,
  `locale`            VARCHAR(10)     NOT NULL DEFAULT 'es',
  `fcm_token`         VARCHAR(255)        NULL DEFAULT NULL COMMENT 'Token Firebase Cloud Messaging (push)',
  `email_verified_at` TIMESTAMP           NULL DEFAULT NULL,
  `remember_token`    VARCHAR(100)        NULL DEFAULT NULL,
  `created_at`        TIMESTAMP           NULL DEFAULT NULL,
  `updated_at`        TIMESTAMP           NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_site_id_index` (`site_id`),
  CONSTRAINT `users_site_id_foreign` FOREIGN KEY (`site_id`) REFERENCES `sitios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLA: licenses
-- Licencias vinculadas a un sitio y un usuario
-- Modelo: App\Models\License
-- ============================================================
CREATE TABLE IF NOT EXISTS `licenses` (
  `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `site_id`     BIGINT UNSIGNED NOT NULL,
  `user_id`     BIGINT UNSIGNED NOT NULL,
  `license_key` VARCHAR(255)    NOT NULL,
  `expires_at`  TIMESTAMP           NULL DEFAULT NULL,
  `created_at`  TIMESTAMP           NULL DEFAULT NULL,
  `updated_at`  TIMESTAMP           NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `licenses_license_key_unique` (`license_key`),
  KEY `licenses_site_id_index` (`site_id`),
  KEY `licenses_user_id_index` (`user_id`),
  CONSTRAINT `licenses_site_id_foreign` FOREIGN KEY (`site_id`) REFERENCES `sitios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `licenses_user_id_foreign` FOREIGN KEY (`user_id`)  REFERENCES `users`  (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLA: role_user
-- Pivote usuario ↔ rol
-- ============================================================
CREATE TABLE IF NOT EXISTS `role_user` (
  `user_id` BIGINT UNSIGNED NOT NULL,
  `role_id` BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (`user_id`, `role_id`),
  KEY `role_user_role_id_foreign` (`role_id`),
  CONSTRAINT `role_user_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_user_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLA: permission_user
-- Pivote usuario ↔ permiso (asignación directa)
-- ============================================================
CREATE TABLE IF NOT EXISTS `permission_user` (
  `user_id`       BIGINT UNSIGNED NOT NULL,
  `permission_id` BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (`user_id`, `permission_id`),
  KEY `permission_user_permission_id_foreign` (`permission_id`),
  CONSTRAINT `permission_user_user_id_foreign`       FOREIGN KEY (`user_id`)       REFERENCES `users`       (`id`) ON DELETE CASCADE,
  CONSTRAINT `permission_user_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLA: role_has_permissions
-- Pivote rol ↔ permiso (Spatie)
-- ============================================================
CREATE TABLE IF NOT EXISTS `role_has_permissions` (
  `permission_id` BIGINT UNSIGNED NOT NULL,
  `role_id`       BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (`permission_id`, `role_id`),
  KEY `role_has_permissions_role_id_foreign` (`role_id`),
  CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_has_permissions_role_id_foreign`       FOREIGN KEY (`role_id`)       REFERENCES `roles`       (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLA: model_has_permissions
-- Permisos asignados a modelos polimórficos (Spatie)
-- ============================================================
CREATE TABLE IF NOT EXISTS `model_has_permissions` (
  `permission_id` BIGINT UNSIGNED NOT NULL,
  `model_type`    VARCHAR(255)    NOT NULL,
  `model_id`      BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (`permission_id`, `model_id`, `model_type`),
  KEY `model_has_permissions_model_id_model_type_index` (`model_id`, `model_type`),
  CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLA: model_has_roles
-- Roles asignados a modelos polimórficos (Spatie)
-- ============================================================
CREATE TABLE IF NOT EXISTS `model_has_roles` (
  `role_id`    BIGINT UNSIGNED NOT NULL,
  `model_type` VARCHAR(255)    NOT NULL,
  `model_id`   BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (`role_id`, `model_id`, `model_type`),
  KEY `model_has_roles_model_id_model_type_index` (`model_id`, `model_type`),
  CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLA: password_resets
-- Tokens de restablecimiento de contraseña (legado)
-- ============================================================
CREATE TABLE IF NOT EXISTS `password_resets` (
  `email`      VARCHAR(255) NOT NULL,
  `token`      VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP        NULL DEFAULT NULL,
  KEY `password_resets_email_index` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLA: password_reset_tokens
-- Tokens de restablecimiento de contraseña (Laravel 10+)
-- ============================================================
CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
  `email`      VARCHAR(255) NOT NULL,
  `token`      VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP        NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLA: personal_access_tokens
-- Tokens de API (Laravel Sanctum)
-- ============================================================
CREATE TABLE IF NOT EXISTS `personal_access_tokens` (
  `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tokenable_type` VARCHAR(255)    NOT NULL,
  `tokenable_id`   BIGINT UNSIGNED NOT NULL,
  `name`           VARCHAR(255)    NOT NULL,
  `token`          VARCHAR(64)     NOT NULL,
  `abilities`      TEXT                NULL DEFAULT NULL,
  `last_used_at`   TIMESTAMP           NULL DEFAULT NULL,
  `expires_at`     TIMESTAMP           NULL DEFAULT NULL,
  `created_at`     TIMESTAMP           NULL DEFAULT NULL,
  `updated_at`     TIMESTAMP           NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`, `tokenable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLA: failed_jobs
-- Cola de trabajos fallidos de Laravel
-- ============================================================
CREATE TABLE IF NOT EXISTS `failed_jobs` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid`       VARCHAR(255)    NOT NULL,
  `connection` TEXT            NOT NULL,
  `queue`      TEXT            NOT NULL,
  `payload`    LONGTEXT        NOT NULL,
  `exception`  LONGTEXT        NOT NULL,
  `failed_at`  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- DATOS INICIALES
-- ============================================================

-- Roles base del sistema
INSERT IGNORE INTO `roles` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES
  (1, 'Administrador', 'web', NOW(), NOW()),
  (2, 'Socio',         'web', NOW(), NOW()),
  (3, 'Empleado',      'web', NOW(), NOW());

-- Permisos base del sistema
INSERT IGNORE INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`) VALUES
  ('Ver usuarios',     'web', NOW(), NOW()),
  ('Crear usuarios',   'web', NOW(), NOW()),
  ('Editar usuarios',  'web', NOW(), NOW()),
  ('Borrar usuarios',  'web', NOW(), NOW()),
  ('Ver familias',     'web', NOW(), NOW()),
  ('Crear familias',   'web', NOW(), NOW()),
  ('Editar familias',  'web', NOW(), NOW()),
  ('Borrar familias',  'web', NOW(), NOW()),
  ('Ver productos',    'web', NOW(), NOW()),
  ('Crear productos',  'web', NOW(), NOW()),
  ('Editar productos', 'web', NOW(), NOW()),
  ('Borrar productos', 'web', NOW(), NOW()),
  ('Ver servicios',    'web', NOW(), NOW()),
  ('Crear servicios',  'web', NOW(), NOW()),
  ('Editar servicios', 'web', NOW(), NOW()),
  ('Borrar servicios', 'web', NOW(), NOW());

SET FOREIGN_KEY_CHECKS = 1;
