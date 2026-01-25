-- =====================================================
-- MIGRACIÓN: SISTEMA DE DIVISIÓN DE CUENTAS
-- Fecha: 2026-01-20
-- Descripción: Permite dividir órdenes en múltiples cuentas
-- =====================================================

-- Tabla para registrar divisiones de cuentas
CREATE TABLE IF NOT EXISTS `ordenes_division_cuentas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `orden_id` int NOT NULL COMMENT 'ID de la orden original',
  `numero_cuentas` int NOT NULL COMMENT 'Número de cuentas en que se dividió',
  `tipo_division` enum('manual','equitativa','por_productos') COLLATE utf32_spanish_ci DEFAULT 'manual' COMMENT 'Tipo de división aplicada',
  `dividida_por_usuario_id` int DEFAULT NULL COMMENT 'Usuario que realizó la división',
  `creada_en` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_orden_id` (`orden_id`),
  CONSTRAINT `fk_division_orden` FOREIGN KEY (`orden_id`) REFERENCES `ordenes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf32 COLLATE=utf32_spanish_ci;

-- Tabla para registrar cada cuenta individual de una división
CREATE TABLE IF NOT EXISTS `division_cuentas_detalle` (
  `id` int NOT NULL AUTO_INCREMENT,
  `division_id` int NOT NULL COMMENT 'ID de la división',
  `numero_cuenta` int NOT NULL COMMENT 'Número de cuenta (1, 2, 3, etc.)',
  `subtotal` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT 'Subtotal de esta cuenta',
  `metodo_pago` enum('efectivo','debito','credito','transferencia') COLLATE utf32_spanish_ci DEFAULT 'efectivo',
  `dinero_recibido` decimal(10,2) DEFAULT NULL COMMENT 'Solo para efectivo',
  `cambio` decimal(10,2) DEFAULT NULL COMMENT 'Solo para efectivo',
  `estado` enum('pendiente','pagada') COLLATE utf32_spanish_ci DEFAULT 'pendiente',
  `pagada_en` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_division_id` (`division_id`),
  CONSTRAINT `fk_detalle_division` FOREIGN KEY (`division_id`) REFERENCES `ordenes_division_cuentas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf32 COLLATE=utf32_spanish_ci;

-- Tabla para asignar productos a cada cuenta dividida
CREATE TABLE IF NOT EXISTS `division_cuentas_productos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cuenta_detalle_id` int NOT NULL COMMENT 'ID del detalle de cuenta',
  `orden_producto_id` int NOT NULL COMMENT 'ID del producto en orden_productos',
  `cantidad_asignada` int NOT NULL DEFAULT '1' COMMENT 'Cantidad asignada a esta cuenta',
  `precio_unitario` decimal(10,2) NOT NULL COMMENT 'Precio unitario del producto',
  `subtotal_producto` decimal(10,2) NOT NULL COMMENT 'Subtotal = cantidad_asignada * precio_unitario',
  PRIMARY KEY (`id`),
  KEY `idx_cuenta_detalle` (`cuenta_detalle_id`),
  KEY `idx_orden_producto` (`orden_producto_id`),
  CONSTRAINT `fk_productos_cuenta` FOREIGN KEY (`cuenta_detalle_id`) REFERENCES `division_cuentas_detalle` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_productos_orden` FOREIGN KEY (`orden_producto_id`) REFERENCES `orden_productos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf32 COLLATE=utf32_spanish_ci;

-- Agregar campo a la tabla ordenes para indicar si fue dividida
ALTER TABLE `ordenes` 
ADD COLUMN IF NOT EXISTS `fue_dividida` tinyint(1) DEFAULT '0' COMMENT 'Indica si la orden fue dividida en múltiples cuentas';

-- =====================================================
-- NOTA IMPORTANTE:
-- Después de ejecutar este script, verifica que:
-- 1. Las tablas se hayan creado correctamente
-- 2. Las relaciones (FOREIGN KEYS) estén funcionando
-- 3. El campo 'fue_dividida' se agregó a la tabla ordenes
-- =====================================================
