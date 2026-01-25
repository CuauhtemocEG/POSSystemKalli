-- Script para crear/actualizar estructura de promociones

-- 1. Crear tabla de promociones si no existe
CREATE TABLE IF NOT EXISTS `promociones` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(200) COLLATE utf32_spanish_ci NOT NULL,
  `descripcion` text COLLATE utf32_spanish_ci,
  `tipo` enum('2x1','3x2','descuento_porcentaje','descuento_fijo','descuento_personal','combo') COLLATE utf32_spanish_ci NOT NULL,
  `valor` decimal(10,2) DEFAULT NULL,
  `aplica_a` enum('productos','categorias','todos') COLLATE utf32_spanish_ci NOT NULL DEFAULT 'todos',
  `activa` tinyint(1) DEFAULT '1',
  `fecha_inicio` datetime DEFAULT NULL,
  `fecha_fin` datetime DEFAULT NULL,
  `prioridad` int DEFAULT '0',
  `aplicar_mayor_valor` tinyint(1) DEFAULT '1',
  `minimo_productos` int DEFAULT '1',
  `creada_en` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `actualizada_en` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_activa` (`activa`),
  KEY `idx_tipo` (`tipo`),
  KEY `idx_fechas` (`fecha_inicio`,`fecha_fin`)
) ENGINE=InnoDB DEFAULT CHARSET=utf32 COLLATE=utf32_spanish_ci;

-- 2. Crear tabla de productos de promociones
CREATE TABLE IF NOT EXISTS `promocion_productos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `promocion_id` int NOT NULL,
  `producto_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_promocion` (`promocion_id`),
  KEY `idx_producto` (`producto_id`),
  CONSTRAINT `fk_promo_productos_promo` FOREIGN KEY (`promocion_id`) REFERENCES `promociones` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_promo_productos_prod` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf32 COLLATE=utf32_spanish_ci;

-- 3. Crear tabla de categorías de promociones
CREATE TABLE IF NOT EXISTS `promocion_categorias` (
  `id` int NOT NULL AUTO_INCREMENT,
  `promocion_id` int NOT NULL,
  `categoria` varchar(50) COLLATE utf32_spanish_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_promocion` (`promocion_id`),
  KEY `idx_categoria` (`categoria`),
  CONSTRAINT `fk_promo_categorias` FOREIGN KEY (`promocion_id`) REFERENCES `promociones` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf32 COLLATE=utf32_spanish_ci;

-- 4. Crear tabla de promociones aplicadas (CRÍTICA para reportes)
CREATE TABLE IF NOT EXISTS `promociones_aplicadas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `orden_id` int NOT NULL,
  `promocion_id` int NOT NULL,
  `descuento_aplicado` decimal(10,2) NOT NULL DEFAULT '0.00',
  `productos_afectados` text COLLATE utf32_spanish_ci,
  `creada_en` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_orden` (`orden_id`),
  KEY `idx_promocion` (`promocion_id`),
  KEY `idx_orden_promo` (`orden_id`, `promocion_id`),
  CONSTRAINT `fk_promo_aplicadas_orden` FOREIGN KEY (`orden_id`) REFERENCES `ordenes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_promo_aplicadas_promo` FOREIGN KEY (`promocion_id`) REFERENCES `promociones` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf32 COLLATE=utf32_spanish_ci;

-- 5. Agregar campos a tabla mesas para control de promociones
ALTER TABLE `mesas` 
ADD COLUMN `aplicar_promociones` tinyint(1) DEFAULT '1' COMMENT 'Si esta mesa aplica promociones activas',
ADD COLUMN `es_para_llevar` tinyint(1) DEFAULT '0' COMMENT 'Si es mesa para llevar (no aplica promos)';

-- 6. Agregar campo subtotal a ordenes para tracking correcto
ALTER TABLE `ordenes`
ADD COLUMN IF NOT EXISTS `subtotal` decimal(10,2) DEFAULT '0.00' COMMENT 'Subtotal antes de descuentos';

-- 7. Índices adicionales para optimización
CREATE INDEX IF NOT EXISTS `idx_ordenes_estado_fecha` ON `ordenes` (`estado`, `creada_en`);
CREATE INDEX IF NOT EXISTS `idx_orden_productos_orden` ON `orden_productos` (`orden_id`, `cancelado`);

-- Verificar estructura
SELECT 'Tablas de promociones creadas/actualizadas correctamente' as status;
