-- ============================================================
-- BASE DE DATOS DEL SITIO (tenant) - Mezzix
-- ============================================================
-- Descripción : BD por cada sitio/local. Contiene la lógica
--               de negocio: fichas, productos, servicios,
--               mesas, albaranes, reservas, etc.
-- Conexión    : 'site' (config/database.php)
--               (configurada dinámicamente por middleware según
--                los datos de la tabla central_db.sitios)
-- Charset     : utf8mb4 / utf8mb4_unicode_ci
--
-- INSTRUCCIONES:
--   Reemplaza <NOMBRE_BD_SITIO> por el valor de sitios.db_name
--   antes de ejecutar el script.
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
-- Crear / seleccionar base de datos del sitio
-- Sustituir <NOMBRE_BD_SITIO> por el nombre real (p.ej. eldespiste)
-- ------------------------------------------------------------
CREATE DATABASE IF NOT EXISTS `<NOMBRE_BD_SITIO>`
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

USE `<NOMBRE_BD_SITIO>`;

-- ============================================================
-- TABLA: migrations
-- Registro interno de migraciones de Laravel
-- ============================================================
CREATE TABLE IF NOT EXISTS `migrations` (
  `id`        INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `migration` VARCHAR(255) NOT NULL,
  `batch`     INT          NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLA: failed_jobs
-- Cola de trabajos fallidos de Laravel (por sitio)
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
-- TABLA: ajustes
-- Configuración general del sitio (una sola fila)
-- Modelo: App\Models\Ajustes
-- ============================================================
CREATE TABLE IF NOT EXISTS `ajustes` (
  `id`                              BIGINT UNSIGNED  NOT NULL AUTO_INCREMENT,
  -- Configuración de fichas / invitados
  `precio_invitado`                 DECIMAL(10,2)        NULL DEFAULT NULL,
  `max_invitados_cobrar`            INT                  NULL DEFAULT NULL,
  `primer_invitado_gratis`          TINYINT(1)       NOT NULL DEFAULT 0,
  `activar_invitados_grupo`         TINYINT(1)       NOT NULL DEFAULT 0,
  -- Stock
  `permitir_comprar_sin_stock`      TINYINT(1)       NOT NULL DEFAULT 0,
  `stock_minimo`                    INT              NOT NULL DEFAULT 0,
  `notificar_stock_bajo`            TINYINT(1)       NOT NULL DEFAULT 0,
  -- Facturación
  `facturar_ficha_automaticamente`  TINYINT(1)       NOT NULL DEFAULT 0,
  `permitir_lectura_codigo_barras`  TINYINT(1)       NOT NULL DEFAULT 0,
  `limite_inscripcion_dias_eventos` INT              NOT NULL DEFAULT 0,
  -- Correo del sitio
  `mail_mailer`                     VARCHAR(50)          NULL DEFAULT NULL,
  `mail_host`                       VARCHAR(255)         NULL DEFAULT NULL,
  `mail_port`                       SMALLINT UNSIGNED    NULL DEFAULT NULL,
  `mail_username`                   VARCHAR(255)         NULL DEFAULT NULL,
  `mail_password`                   VARCHAR(255)         NULL DEFAULT NULL,
  `mail_encryption`                 VARCHAR(20)          NULL DEFAULT NULL,
  `mail_from_address`               VARCHAR(255)         NULL DEFAULT NULL,
  `mail_from_name`                  VARCHAR(255)         NULL DEFAULT NULL,
  -- Modo de operación
  `modo_operacion`                  ENUM('fichas','mesas') NOT NULL DEFAULT 'fichas',
  `mostrar_usuarios`                TINYINT(1)       NOT NULL DEFAULT 1,
  `mostrar_gastos`                  TINYINT(1)       NOT NULL DEFAULT 1,
  `mostrar_compras`                 TINYINT(1)       NOT NULL DEFAULT 1,
  -- Recordatorios de reservas
  `recordatorio_reservas_minutos`   INT              NOT NULL DEFAULT 60,
  `recordatorio_reservas_email`     TINYINT(1)       NOT NULL DEFAULT 0,
  `recordatorio_reservas_push`      TINYINT(1)       NOT NULL DEFAULT 0,
  `created_at`                      TIMESTAMP            NULL DEFAULT NULL,
  `updated_at`                      TIMESTAMP            NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLA: familias
-- Categorías / familias de productos
-- Modelo: App\Models\Familia
-- ============================================================
CREATE TABLE IF NOT EXISTS `familias` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre`     VARCHAR(255)    NOT NULL,
  `imagen`     VARCHAR(255)    NOT NULL DEFAULT '',
  `posicion`   INT             NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP           NULL DEFAULT NULL,
  `updated_at` TIMESTAMP           NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLA: productos
-- Catálogo de productos del sitio
-- Modelo: App\Models\Producto (PK: uuid)
-- ============================================================
CREATE TABLE IF NOT EXISTS `productos` (
  `uuid`            CHAR(36)        NOT NULL,
  `nombre`          VARCHAR(255)    NOT NULL,
  `imagen`          VARCHAR(255)    NOT NULL DEFAULT '',
  `posicion`        INT             NOT NULL DEFAULT 0,
  `familia`         BIGINT UNSIGNED NOT NULL COMMENT 'ID de familias',
  `combinado`       TINYINT(1)      NOT NULL DEFAULT 0 COMMENT '1 = es un combinado',
  `precio`          DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
  `ean13`           VARCHAR(20)         NULL DEFAULT NULL COMMENT 'Código de barras EAN-13',
  `iva`             DECIMAL(5,2)    NOT NULL DEFAULT 0.00 COMMENT 'Tipo de IVA en %',
  `stock`           INT                 NULL DEFAULT NULL COMMENT 'NULL = sin control de stock',
  `stock_reservado` INT             NOT NULL DEFAULT 0,
  `created_at`      TIMESTAMP           NULL DEFAULT NULL,
  `updated_at`      TIMESTAMP           NULL DEFAULT NULL,
  PRIMARY KEY (`uuid`),
  KEY `productos_familia_index` (`familia`),
  KEY `productos_ean13_index` (`ean13`),
  CONSTRAINT `productos_familia_foreign` FOREIGN KEY (`familia`) REFERENCES `familias` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLA: composicion_productos
-- Componentes de un producto combinado
-- Modelo: App\Models\ComposicionProducto
-- ============================================================
CREATE TABLE IF NOT EXISTS `composicion_productos` (
  `uuid`           CHAR(36)     NOT NULL,
  `id_producto`    CHAR(36)     NOT NULL COMMENT 'Producto padre (uuid)',
  `id_componente`  CHAR(36)     NOT NULL COMMENT 'Producto componente (uuid)',
  `created_at`     TIMESTAMP        NULL DEFAULT NULL,
  `updated_at`     TIMESTAMP        NULL DEFAULT NULL,
  PRIMARY KEY (`uuid`),
  KEY `composicion_productos_id_producto_index`   (`id_producto`),
  KEY `composicion_productos_id_componente_index` (`id_componente`),
  CONSTRAINT `composicion_id_producto_foreign`   FOREIGN KEY (`id_producto`)   REFERENCES `productos` (`uuid`) ON DELETE CASCADE,
  CONSTRAINT `composicion_id_componente_foreign` FOREIGN KEY (`id_componente`) REFERENCES `productos` (`uuid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLA: servicios
-- Servicios adicionales del sitio (uso de cocina, sala, etc.)
-- Modelo: App\Models\Servicio (PK: uuid)
-- ============================================================
CREATE TABLE IF NOT EXISTS `servicios` (
  `uuid`       CHAR(36)      NOT NULL,
  `nombre`     VARCHAR(255)  NOT NULL,
  `posicion`   INT           NOT NULL DEFAULT 0,
  `precio`     DECIMAL(8,2)  NOT NULL DEFAULT 0.00,
  `iva`        DECIMAL(5,2)  NOT NULL DEFAULT 0.00 COMMENT 'Tipo de IVA en %',
  `created_at` TIMESTAMP         NULL DEFAULT NULL,
  `updated_at` TIMESTAMP         NULL DEFAULT NULL,
  PRIMARY KEY (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLA: proveedores
-- Proveedores del sitio
-- Modelo: App\Models\Proveedor (soft deletes)
-- ============================================================
CREATE TABLE IF NOT EXISTS `proveedores` (
  `id`                 BIGINT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `uuid`               CHAR(36)         NOT NULL,
  `nombre`             VARCHAR(255)     NOT NULL,
  `cif`                VARCHAR(50)          NULL DEFAULT NULL,
  `email`              VARCHAR(255)         NULL DEFAULT NULL,
  `telefono`           VARCHAR(50)          NULL DEFAULT NULL,
  `direccion`          VARCHAR(255)         NULL DEFAULT NULL,
  `ciudad`             VARCHAR(100)         NULL DEFAULT NULL,
  `codigo_postal`      VARCHAR(10)          NULL DEFAULT NULL,
  `pais`               VARCHAR(100)         NULL DEFAULT NULL,
  `contacto_principal` VARCHAR(255)         NULL DEFAULT NULL,
  `condiciones_pago`   VARCHAR(255)         NULL DEFAULT NULL,
  `dias_pago`          INT              NOT NULL DEFAULT 30,
  `cuenta_bancaria`    VARCHAR(50)          NULL DEFAULT NULL,
  `notas`              TEXT                 NULL DEFAULT NULL,
  `activo`             TINYINT(1)       NOT NULL DEFAULT 1,
  `descuento_general`  DECIMAL(5,2)     NOT NULL DEFAULT 0.00,
  `created_at`         TIMESTAMP            NULL DEFAULT NULL,
  `updated_at`         TIMESTAMP            NULL DEFAULT NULL,
  `deleted_at`         TIMESTAMP            NULL DEFAULT NULL COMMENT 'Soft delete',
  PRIMARY KEY (`id`),
  UNIQUE KEY `proveedores_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLA: fichas
-- Fichas de socios / cuentas de mesa / eventos
-- Modelo: App\Models\Ficha (PK: uuid)
-- ============================================================
CREATE TABLE IF NOT EXISTS `fichas` (
  `uuid`               CHAR(36)                                          NOT NULL,
  -- Datos del socio / propietario
  `user_id`            BIGINT UNSIGNED                                       NULL DEFAULT NULL COMMENT 'FK a central_db.users',
  -- Tipo y estado general
  `tipo`               VARCHAR(50)                                           NULL DEFAULT NULL COMMENT 'ficha | evento | reserva',
  `estado`             VARCHAR(50)                                           NULL DEFAULT 'abierta',
  `descripcion`        TEXT                                                  NULL DEFAULT NULL,
  -- Invitados / grupo
  `invitados_grupo`    INT                                                   NULL DEFAULT 0,
  -- Fecha y precio acordado
  `fecha`              DATE                                                  NULL DEFAULT NULL,
  `precio`             DECIMAL(10,2)                                     NOT NULL DEFAULT 0.00,
  `hora`               TIME                                                  NULL DEFAULT NULL,
  -- Carta / menú y responsables
  `menu`               TEXT                                                  NULL DEFAULT NULL,
  `responsables`       TEXT                                                  NULL DEFAULT NULL,
  -- Importe total calculado
  `importe`            DECIMAL(10,2)                                         NULL DEFAULT 0.00,
  -- Datos adicionales
  `nombre`             VARCHAR(255)                                          NULL DEFAULT NULL COMMENT 'Nombre público del evento o reserva',
  `observaciones`      TEXT                                                  NULL DEFAULT NULL,
  -- Evento
  `descripcion_evento` TEXT                                                  NULL DEFAULT NULL,
  `foto_evento`        VARCHAR(255)                                          NULL DEFAULT NULL,
  `ubicacion_evento`   VARCHAR(255)                                          NULL DEFAULT NULL,
  `aforo_maximo`       INT                                                   NULL DEFAULT NULL,
  `inscritos_actuales` INT                                               NOT NULL DEFAULT 0,
  `es_infantil`        TINYINT(1)                                        NOT NULL DEFAULT 0,
  -- ── Campos de sistema de mesas ──────────────────────────────
  `numero_mesa`        VARCHAR(10)                                           NULL DEFAULT NULL,
  `numero_comensales`  INT                                               NOT NULL DEFAULT 0,
  `modo`               ENUM('ficha','mesa')                              NOT NULL DEFAULT 'ficha',
  `estado_mesa`        ENUM('libre','ocupada','cerrada')                     NULL DEFAULT NULL,
  `camarero_id`        BIGINT UNSIGNED                                       NULL DEFAULT NULL COMMENT 'FK a central_db.users',
  `hora_apertura`      DATETIME                                              NULL DEFAULT NULL,
  `hora_cierre`        DATETIME                                              NULL DEFAULT NULL,
  `ultimo_camarero_id` BIGINT UNSIGNED                                       NULL DEFAULT NULL COMMENT 'FK a central_db.users',
  -- ────────────────────────────────────────────────────────────
  `created_at`         TIMESTAMP                                             NULL DEFAULT NULL,
  `updated_at`         TIMESTAMP                                             NULL DEFAULT NULL,
  PRIMARY KEY (`uuid`),
  KEY `fichas_user_id_index`       (`user_id`),
  KEY `fichas_tipo_index`          (`tipo`),
  KEY `fichas_estado_index`        (`estado`),
  KEY `fichas_fecha_index`         (`fecha`),
  KEY `fichas_numero_mesa_index`   (`numero_mesa`),
  KEY `fichas_modo_index`          (`modo`),
  KEY `fichas_estado_mesa_index`   (`estado_mesa`),
  KEY `fichas_camarero_id_index`   (`camarero_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLA: fichas_usuarios
-- Inscritos / participantes de una ficha
-- Modelo: App\Models\FichaUsuario
-- ============================================================
CREATE TABLE IF NOT EXISTS `fichas_usuarios` (
  `id`       BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid`     CHAR(36)            NULL DEFAULT NULL,
  `id_ficha` CHAR(36)        NOT NULL,
  `user_id`  BIGINT UNSIGNED NOT NULL COMMENT 'FK a central_db.users',
  `invitados` INT            NOT NULL DEFAULT 0,
  `ninos`    INT             NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP         NULL DEFAULT NULL,
  `updated_at` TIMESTAMP         NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fichas_usuarios_id_ficha_index` (`id_ficha`),
  KEY `fichas_usuarios_user_id_index`  (`user_id`),
  CONSTRAINT `fichas_usuarios_id_ficha_foreign` FOREIGN KEY (`id_ficha`) REFERENCES `fichas` (`uuid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLA: fichas_productos
-- Productos consumidos en una ficha / mesa
-- Modelo: App\Models\FichaProducto (PK: uuid)
-- ============================================================
CREATE TABLE IF NOT EXISTS `fichas_productos` (
  `uuid`       CHAR(36)        NOT NULL,
  `id_ficha`   CHAR(36)        NOT NULL,
  `id_producto` CHAR(36)       NOT NULL,
  `cantidad`   INT             NOT NULL DEFAULT 1,
  `precio`     DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
  `estado`     VARCHAR(50)         NULL DEFAULT NULL COMMENT 'pendiente | en_preparacion | listo | entregado',
  `created_at` TIMESTAMP           NULL DEFAULT NULL,
  `updated_at` TIMESTAMP           NULL DEFAULT NULL,
  PRIMARY KEY (`uuid`),
  KEY `fichas_productos_id_ficha_index`    (`id_ficha`),
  KEY `fichas_productos_id_producto_index` (`id_producto`),
  CONSTRAINT `fichas_productos_id_ficha_foreign`    FOREIGN KEY (`id_ficha`)    REFERENCES `fichas`    (`uuid`)  ON DELETE CASCADE,
  CONSTRAINT `fichas_productos_id_producto_foreign` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`uuid`)  ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLA: fichas_gastos
-- Gastos registrados en una ficha
-- Modelo: App\Models\FichaGasto (PK: uuid)
-- ============================================================
CREATE TABLE IF NOT EXISTS `fichas_gastos` (
  `uuid`        CHAR(36)       NOT NULL,
  `id_ficha`    CHAR(36)       NOT NULL,
  `user_id`     BIGINT UNSIGNED    NULL DEFAULT NULL COMMENT 'FK a central_db.users',
  `descripcion` VARCHAR(255)   NOT NULL DEFAULT '',
  `ticket`      VARCHAR(255)       NULL DEFAULT NULL COMMENT 'Ruta imagen del ticket',
  `precio`      DECIMAL(10,2)  NOT NULL DEFAULT 0.00,
  `created_at`  TIMESTAMP          NULL DEFAULT NULL,
  `updated_at`  TIMESTAMP          NULL DEFAULT NULL,
  PRIMARY KEY (`uuid`),
  KEY `fichas_gastos_id_ficha_index` (`id_ficha`),
  CONSTRAINT `fichas_gastos_id_ficha_foreign` FOREIGN KEY (`id_ficha`) REFERENCES `fichas` (`uuid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLA: fichas_recibos
-- Recibos de pago generados para una ficha
-- Modelo: App\Models\FichaRecibo / Recibo (PK: uuid)
-- ============================================================
CREATE TABLE IF NOT EXISTS `fichas_recibos` (
  `uuid`       CHAR(36)       NOT NULL,
  `id_ficha`   CHAR(36)       NOT NULL,
  `user_id`    BIGINT UNSIGNED    NULL DEFAULT NULL COMMENT 'FK a central_db.users',
  `tipo`       VARCHAR(50)        NULL DEFAULT NULL COMMENT 'efectivo | tarjeta | transferencia',
  `estado`     VARCHAR(50)        NULL DEFAULT 'pendiente',
  `precio`     DECIMAL(10,2)  NOT NULL DEFAULT 0.00,
  `fecha`      DATE               NULL DEFAULT NULL,
  `created_at` TIMESTAMP          NULL DEFAULT NULL,
  `updated_at` TIMESTAMP          NULL DEFAULT NULL,
  PRIMARY KEY (`uuid`),
  KEY `fichas_recibos_id_ficha_index` (`id_ficha`),
  CONSTRAINT `fichas_recibos_id_ficha_foreign` FOREIGN KEY (`id_ficha`) REFERENCES `fichas` (`uuid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLA: fichas_servicios
-- Servicios añadidos a una ficha
-- Modelo: App\Models\FichaServicio (PK: uuid)
-- ============================================================
CREATE TABLE IF NOT EXISTS `fichas_servicios` (
  `uuid`        CHAR(36)      NOT NULL,
  `id_ficha`    CHAR(36)      NOT NULL,
  `id_servicio` CHAR(36)      NOT NULL,
  `precio`      DECIMAL(8,2)  NOT NULL DEFAULT 0.00,
  `created_at`  TIMESTAMP         NULL DEFAULT NULL,
  `updated_at`  TIMESTAMP         NULL DEFAULT NULL,
  PRIMARY KEY (`uuid`),
  KEY `fichas_servicios_id_ficha_index`    (`id_ficha`),
  KEY `fichas_servicios_id_servicio_index` (`id_servicio`),
  CONSTRAINT `fichas_servicios_id_ficha_foreign`    FOREIGN KEY (`id_ficha`)    REFERENCES `fichas`    (`uuid`)  ON DELETE CASCADE,
  CONSTRAINT `fichas_servicios_id_servicio_foreign` FOREIGN KEY (`id_servicio`) REFERENCES `servicios` (`uuid`)  ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLA: reservas
-- Reservas de espacios / horarios
-- Modelo: App\Models\Reserva (PK: uuid)
-- ============================================================
CREATE TABLE IF NOT EXISTS `reservas` (
  `id`                          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid`                        CHAR(36)        NOT NULL,
  `name`                        VARCHAR(255)    NOT NULL DEFAULT '',
  `user_id`                     BIGINT UNSIGNED     NULL DEFAULT NULL COMMENT 'FK a central_db.users',
  `start_time`                  DATETIME            NULL DEFAULT NULL,
  `end_time`                    DATETIME            NULL DEFAULT NULL,
  `notificado_recordatorio`     TINYINT(1)      NOT NULL DEFAULT 0,
  `created_at`                  TIMESTAMP           NULL DEFAULT NULL,
  `updated_at`                  TIMESTAMP           NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `reservas_uuid_unique` (`uuid`),
  KEY `reservas_user_id_index`    (`user_id`),
  KEY `reservas_start_time_index` (`start_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLA: albaranes
-- Albaranes de compra a proveedores
-- Modelo: App\Models\Albaran
-- ============================================================
CREATE TABLE IF NOT EXISTS `albaranes` (
  `id`               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `proveedor_id`     BIGINT UNSIGNED NOT NULL,
  `numero_albaran`   VARCHAR(100)        NULL DEFAULT NULL,
  `fecha_albaran`    DATE                NULL DEFAULT NULL,
  `fecha`            DATE                NULL DEFAULT NULL COMMENT 'Fecha de registro',
  `estado`           VARCHAR(50)     NOT NULL DEFAULT 'pendiente' COMMENT 'pendiente | recibido | cancelado',
  `total`            DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
  `observaciones`    TEXT                NULL DEFAULT NULL,
  `usuario_id`       BIGINT UNSIGNED     NULL DEFAULT NULL COMMENT 'FK a central_db.users',
  `fecha_recepcion`  DATETIME            NULL DEFAULT NULL,
  `created_at`       TIMESTAMP           NULL DEFAULT NULL,
  `updated_at`       TIMESTAMP           NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `albaranes_proveedor_id_index` (`proveedor_id`),
  KEY `albaranes_estado_index`       (`estado`),
  KEY `albaranes_fecha_index`        (`fecha`),
  CONSTRAINT `albaranes_proveedor_id_foreign` FOREIGN KEY (`proveedor_id`) REFERENCES `proveedores` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLA: albaran_lineas
-- Líneas de detalle de un albarán
-- Modelo: App\Models\AlbaranLinea
-- ============================================================
CREATE TABLE IF NOT EXISTS `albaran_lineas` (
  `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `albaran_id`   BIGINT UNSIGNED NOT NULL,
  `producto_id`  CHAR(36)        NOT NULL COMMENT 'FK productos.uuid',
  `cantidad`     DECIMAL(10,2)   NOT NULL DEFAULT 1.00,
  `precio_coste` DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
  `subtotal`     DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
  `created_at`   TIMESTAMP           NULL DEFAULT NULL,
  `updated_at`   TIMESTAMP           NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `albaran_lineas_albaran_id_index`  (`albaran_id`),
  KEY `albaran_lineas_producto_id_index` (`producto_id`),
  CONSTRAINT `albaran_lineas_albaran_id_foreign`  FOREIGN KEY (`albaran_id`)  REFERENCES `albaranes`  (`id`)   ON DELETE CASCADE,
  CONSTRAINT `albaran_lineas_producto_id_foreign` FOREIGN KEY (`producto_id`) REFERENCES `productos`  (`uuid`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLA: facturas_mesas
-- Facturas generadas al cerrar una mesa
-- Modelo: App\Models\FacturaMesa
-- ============================================================
CREATE TABLE IF NOT EXISTS `facturas_mesas` (
  `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `numero_factura`  VARCHAR(20)     NOT NULL COMMENT 'Formato: YYYY/NNN',
  `fecha`           DATE            NOT NULL,
  `mesa_id`         CHAR(36)            NULL DEFAULT NULL COMMENT 'FK fichas.uuid',
  `camarero_id`     BIGINT UNSIGNED     NULL DEFAULT NULL COMMENT 'FK a central_db.users',
  `cliente_nombre`  VARCHAR(255)        NULL DEFAULT NULL,
  `cliente_nif`     VARCHAR(20)         NULL DEFAULT NULL,
  `subtotal`        DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
  `total_iva`       DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
  `total`           DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
  `detalles`        JSON                NULL DEFAULT NULL COMMENT 'Líneas de detalle en JSON',
  PRIMARY KEY (`id`),
  UNIQUE KEY `facturas_mesas_numero_factura_unique` (`numero_factura`),
  KEY `facturas_mesas_mesa_id_index`    (`mesa_id`),
  KEY `facturas_mesas_fecha_index`      (`fecha`),
  CONSTRAINT `facturas_mesas_mesa_id_foreign` FOREIGN KEY (`mesa_id`) REFERENCES `fichas` (`uuid`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLA: mesa_historial
-- Registro de acciones sobre las mesas
-- Modelo: App\Models\MesaHistorial
-- ============================================================
CREATE TABLE IF NOT EXISTS `mesa_historial` (
  `id`                    BIGINT UNSIGNED                                                       NOT NULL AUTO_INCREMENT,
  `mesa_id`               CHAR(36)                                                              NOT NULL COMMENT 'FK fichas.uuid',
  `accion`                ENUM('abrir','tomar','añadir_consumo','cerrar','liberar')             NOT NULL,
  `camarero_id`           BIGINT UNSIGNED                                                           NULL DEFAULT NULL COMMENT 'FK a central_db.users',
  `camarero_anterior_id`  BIGINT UNSIGNED                                                           NULL DEFAULT NULL COMMENT 'FK a central_db.users',
  `detalles`              JSON                                                                      NULL DEFAULT NULL,
  `fecha_accion`          DATETIME                                                             NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at`            TIMESTAMP                                                                 NULL DEFAULT NULL,
  `updated_at`            TIMESTAMP                                                                 NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `mesa_historial_mesa_id_index`    (`mesa_id`),
  KEY `mesa_historial_camarero_id_index` (`camarero_id`),
  KEY `mesa_historial_fecha_accion_index` (`fecha_accion`),
  CONSTRAINT `mesa_historial_mesa_id_foreign` FOREIGN KEY (`mesa_id`) REFERENCES `fichas` (`uuid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- DATOS INICIALES
-- ============================================================

-- Fila de ajustes por defecto (siempre existe una sola fila)
INSERT IGNORE INTO `ajustes` (
  `id`, `precio_invitado`, `max_invitados_cobrar`, `primer_invitado_gratis`,
  `activar_invitados_grupo`, `permitir_comprar_sin_stock`, `stock_minimo`,
  `notificar_stock_bajo`, `facturar_ficha_automaticamente`,
  `permitir_lectura_codigo_barras`, `limite_inscripcion_dias_eventos`,
  `modo_operacion`, `mostrar_usuarios`, `mostrar_gastos`, `mostrar_compras`,
  `recordatorio_reservas_minutos`, `recordatorio_reservas_email`, `recordatorio_reservas_push`,
  `created_at`, `updated_at`
) VALUES (
  1, NULL, NULL, 0,
  0, 0, 0,
  0, 0,
  0, 0,
  'fichas', 1, 1, 1,
  60, 0, 0,
  NOW(), NOW()
);

SET FOREIGN_KEY_CHECKS = 1;
