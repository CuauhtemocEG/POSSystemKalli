-- Script para agregar funcionalidad de descuento manual por porcentaje en órdenes
-- Fecha: 2026-01-24

-- Agregar campos para controlar descuento porcentaje manual por orden
ALTER TABLE ordenes 
ADD COLUMN aplicar_descuento_porcentaje TINYINT(1) DEFAULT 0 COMMENT 'Activar descuento % manual en esta orden',
ADD COLUMN descuento_porcentaje_valor DECIMAL(5,2) DEFAULT 0.00 COMMENT 'Porcentaje de descuento manual aplicado (0-100)';

-- Crear índice para búsquedas
CREATE INDEX idx_ordenes_descuento_manual ON ordenes(aplicar_descuento_porcentaje);
