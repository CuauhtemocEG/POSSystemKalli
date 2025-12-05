-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost:8889
-- Tiempo de generación: 21-11-2025 a las 20:28:53
-- Versión del servidor: 8.0.40
-- Versión de PHP: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `KalliJaguarPOS`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `codigos_cancelacion`
--

CREATE TABLE `codigos_cancelacion` (
  `id` int NOT NULL,
  `codigo` varchar(6) COLLATE utf32_spanish_ci NOT NULL,
  `orden_id` int NOT NULL,
  `producto_id` int NOT NULL,
  `cantidad_solicitada` int DEFAULT '1',
  `solicitado_por` int NOT NULL,
  `autorizado_por` int DEFAULT NULL,
  `razon` text COLLATE utf32_spanish_ci,
  `usado` tinyint(1) DEFAULT '0',
  `fecha_creacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_expiracion` timestamp NOT NULL,
  `fecha_autorizacion` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf32 COLLATE=utf32_spanish_ci;

--
-- Volcado de datos para la tabla `codigos_cancelacion`
--

INSERT INTO `codigos_cancelacion` (`id`, `codigo`, `orden_id`, `producto_id`, `cantidad_solicitada`, `solicitado_por`, `autorizado_por`, `razon`, `usado`, `fecha_creacion`, `fecha_expiracion`, `fecha_autorizacion`) VALUES
(1, '882491', 4, 15, 1, 2, 1, 'Equivocación del cliente', 1, '2025-11-20 00:22:28', '2025-11-20 00:32:28', '2025-11-20 00:23:22'),
(2, '182727', 7, 6, 1, 1, 1, 'Error de cliente pide nuevo producto.', 1, '2025-11-20 18:14:58', '2025-11-20 18:24:58', '2025-11-20 18:21:00'),
(3, '926794', 7, 15, 1, 1, 1, 'Testing', 1, '2025-11-20 18:48:04', '2025-11-20 18:58:04', '2025-11-20 18:56:46'),
(4, '180547', 7, 8, 1, 1, NULL, 'Testing', 1, '2025-11-20 19:10:41', '2025-11-20 19:20:41', NULL),
(5, '283049', 7, 8, 1, 1, NULL, 'test', 1, '2025-11-20 19:25:49', '2025-11-20 19:35:49', NULL),
(6, '546705', 8, 52, 1, 1, 1, 'Test', 1, '2025-11-20 21:03:29', '2025-11-20 21:13:29', '2025-11-20 21:07:15');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `configuracion`
--

CREATE TABLE `configuracion` (
  `id` int NOT NULL,
  `clave` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `valor` text COLLATE utf8mb4_unicode_ci,
  `tipo` enum('string','integer','boolean','json') COLLATE utf8mb4_unicode_ci DEFAULT 'string',
  `descripcion` text COLLATE utf8mb4_unicode_ci,
  `creado_en` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `actualizado_en` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `configuracion`
--

INSERT INTO `configuracion` (`id`, `clave`, `valor`, `tipo`, `descripcion`, `creado_en`, `actualizado_en`) VALUES
(5, 'empresa_nombre', 'Dorada', 'string', 'Nombre de la empresa', '2025-09-03 01:42:08', '2025-11-20 18:58:14'),
(6, 'empresa_direccion', '39 Oriente 1204-A, Anzures, 72530, Heroica Puebla de Zaragoza', 'string', 'Dirección de la empresa', '2025-09-03 01:42:08', '2025-11-20 18:58:14'),
(7, 'empresa_telefono', '+522232112344', 'string', 'Teléfono de la empresa', '2025-09-03 01:42:08', '2025-11-20 18:58:14'),
(19, 'admin_email_1', 'cencarnacion@kallijaguar-inventory.com', 'string', 'Email administrador principal', '2025-09-03 02:18:04', '2025-09-28 21:39:33'),
(20, 'admin_email_2', 'temoc612@gmail.com', 'string', 'Email administrador secundario', '2025-09-03 02:18:04', '2025-09-28 21:39:33'),
(21, 'notificaciones_habilitadas', '1', 'boolean', NULL, '2025-09-03 02:18:04', '2025-09-03 02:18:04'),
(22, 'usar_modo_prueba', '0', 'boolean', 'Modo de prueba activado', '2025-09-03 02:18:04', '2025-09-28 21:39:33'),
(23, 'smtp_host', 'smtp.titan.email', 'string', 'Servidor SMTP', '2025-09-03 02:44:15', '2025-09-28 21:39:33'),
(24, 'smtp_port', '465', 'string', 'Puerto SMTP', '2025-09-03 02:44:15', '2025-09-28 21:39:33'),
(25, 'smtp_username', 'info@kallijaguar-inventory.com', 'string', 'Usuario SMTP', '2025-09-03 02:44:15', '2025-09-28 21:39:33'),
(26, 'smtp_password', '{&<eXA[x$?_q\\<N', 'string', 'Contraseña SMTP', '2025-09-03 02:44:15', '2025-09-28 21:39:33'),
(27, 'use_smtp', '1', 'boolean', 'Usar SMTP personalizado', '2025-09-03 02:44:15', '2025-09-28 21:39:33'),
(28, 'email_from', 'info@kallijaguar-inventory.com', 'string', 'Email remitente del sistema', '2025-09-03 02:44:15', '2025-09-28 21:39:33'),
(29, 'email_from_name', 'Informacion Kalli Jaguar', 'string', 'Nombre remitente del sistema', '2025-09-03 02:44:15', '2025-09-28 21:39:33'),
(30, 'email_habilitado', '1', 'boolean', 'Email habilitado para códigos PIN', '2025-09-03 02:44:15', '2025-09-28 21:39:33'),
(31, 'email_pin_expiracion', '600', 'integer', 'Tiempo de expiración de códigos PIN en segundos', '2025-09-03 02:44:15', '2025-09-28 21:39:33'),
(165, 'impresion_automatica', '1', 'string', 'Impresión automática térmica habilitada', '2025-09-05 01:06:48', '2025-09-05 22:59:10'),
(166, 'metodo_impresion', 'local', 'string', 'Método de impresión térmica', '2025-09-05 01:06:48', '2025-09-05 22:59:10'),
(167, 'nombre_impresora', 'Gprinter_Termica', 'string', 'Nombre de la impresora térmica', '2025-09-05 01:06:48', '2025-09-05 22:59:10'),
(168, 'ip_impresora', '', 'string', 'IP de la impresora térmica', '2025-09-05 01:06:48', '2025-09-05 22:59:10'),
(169, 'puerto_impresora', '9100', 'string', 'Puerto de la impresora térmica', '2025-09-05 01:06:48', '2025-09-05 22:59:10'),
(170, 'ancho_papel', '80', 'string', 'Ancho de papel térmico en mm', '2025-09-05 01:06:48', '2025-09-05 22:59:10'),
(171, 'copias_ticket', '1', 'string', 'Número de copias por ticket', '2025-09-05 01:06:48', '2025-09-05 22:59:10'),
(172, 'corte_automatico', '1', 'string', 'Corte automático de papel', '2025-09-05 01:06:48', '2025-09-05 22:59:10'),
(293, 'logo_activado', '1', 'string', 'Logo activado en tickets', '2025-09-05 22:19:44', '2025-09-05 22:59:10'),
(294, 'logo_imagen', 'logoorange.jpg', 'string', 'Imagen del logo para tickets', '2025-09-05 22:19:44', '2025-09-05 22:59:10'),
(295, 'logo_tamaño', 'grande', 'string', 'Tamaño del logo en tickets', '2025-09-05 22:19:44', '2025-09-05 22:59:10'),
(354, 'empresa_email', '', 'string', 'Email de la empresa', '2025-09-05 22:56:32', '2025-11-20 18:58:14'),
(355, 'empresa_rfc', '', 'string', 'RFC de la empresa', '2025-09-05 22:56:32', '2025-11-20 18:58:14');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `historial_ordenes`
--

CREATE TABLE `historial_ordenes` (
  `id` int NOT NULL,
  `orden_id` int NOT NULL,
  `accion` varchar(50) CHARACTER SET utf32 COLLATE utf32_spanish_ci NOT NULL,
  `detalle` text CHARACTER SET utf32 COLLATE utf32_spanish_ci,
  `usuario_id` int DEFAULT NULL,
  `fecha_accion` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf32 COLLATE=utf32_spanish_ci;

--
-- Volcado de datos para la tabla `historial_ordenes`
--

INSERT INTO `historial_ordenes` (`id`, `orden_id`, `accion`, `detalle`, `usuario_id`, `fecha_accion`) VALUES
(1, 1, 'ORDEN_CERRADA', 'Orden cerrada exitosamente. Total: $921.00. Método de pago: Efectivo. Dinero recibido: $1,000.00. Cambio: $79.00. Mesa: A-1', 2, '2025-11-19 23:48:43'),
(2, 3, 'ORDEN_CERRADA', 'Orden cerrada exitosamente. Total: $191.00. Método de pago: Efectivo. Dinero recibido: $200.00. Cambio: $9.00. Mesa: A-2', 2, '2025-11-20 00:20:19'),
(3, 2, 'ORDEN_CERRADA', 'Orden cerrada exitosamente. Total: $351.00. Método de pago: Efectivo. Dinero recibido: $400.00. Cambio: $49.00. Mesa: A-1', 2, '2025-11-20 00:21:17'),
(4, 4, 'SOLICITUD_CANCELACION', 'Solicitud de cancelación para producto: Cerveza Victoria. Cantidad: 1 unidad(es). PIN: 882491. Enviado por email a 2/2 administrador(es). Razón: Equivocación del cliente', 2, '2025-11-20 00:22:31'),
(5, 4, 'ORDEN_CERRADA', 'Orden cerrada exitosamente. Total: $682.00. Método de pago: Transferencia. Mesa: A-3', 1, '2025-11-20 01:55:11'),
(6, 5, 'ORDEN_CERRADA', 'Orden cerrada exitosamente. Total: $897.00. Método de pago: Crédito. Mesa: A-1', 1, '2025-11-20 03:53:29'),
(7, 6, 'ORDEN_CERRADA', 'Orden cerrada exitosamente. Total: $769.00. Método de pago: Débito. Mesa: A-2', 1, '2025-11-20 03:53:36'),
(8, 7, 'SOLICITUD_CANCELACION', 'Solicitud de cancelación para producto: Chalupas Chilapeñas. Cantidad: 1 unidad(es). PIN: 182727. Enviado por email a 2/2 administrador(es). Razón: Error de cliente pide nuevo producto.', 1, '2025-11-20 18:15:00'),
(9, 7, 'SOLICITUD_CANCELACION', 'Solicitud de cancelación para producto: Cerveza Victoria. Cantidad: 1 unidad(es). PIN: 926794. Enviado por email a 2/2 administrador(es). Razón: Testing', 1, '2025-11-20 18:48:07'),
(10, 7, 'SOLICITUD_CANCELACION', 'Solicitud de cancelación para producto: Chilate 500 ml. Cantidad: 1 unidad(es). PIN: 180547. Enviado por email a 2/2 administrador(es). Razón: Testing', 1, '2025-11-20 19:10:43'),
(11, 7, 'SOLICITUD_CANCELACION', 'Solicitud de cancelación para producto: Chilate 500 ml. Cantidad: 1 unidad(es). PIN: 283049. Enviado por email a 2/2 administrador(es). Razón: test', 1, '2025-11-20 19:25:51'),
(12, 7, 'ORDEN_CERRADA', 'Orden cerrada exitosamente. Total: $1,570.00. Método de pago: Efectivo. Dinero recibido: $2,000.00. Cambio: $430.00. Mesa: A-1', 1, '2025-11-20 20:54:05'),
(13, 8, 'SOLICITUD_CANCELACION', 'Solicitud de cancelación para producto: Pozole Blanco Grande. Cantidad: 1 unidad(es). PIN: 546705. Enviado por email a 2/2 administrador(es). Razón: Test', 1, '2025-11-20 21:03:31'),
(14, 8, 'ORDEN_CERRADA', 'Orden cerrada exitosamente. Total: $230.00. Método de pago: Débito. Mesa: A-1', 1, '2025-11-20 21:11:05'),
(15, 9, 'ORDEN_CERRADA', 'Orden cerrada exitosamente. Total: $167.00. Método de pago: Efectivo. Dinero recibido: $200.00. Cambio: $33.00. Mesa: A-1', 1, '2025-11-20 21:51:58'),
(16, 10, 'ORDEN_CERRADA', 'Orden cerrada exitosamente. Total: $736.00. Método de pago: Efectivo. Dinero recibido: $1,000.00. Cambio: $264.00. Mesa: A-1', 1, '2025-11-21 00:07:04'),
(17, 11, 'ORDEN_CERRADA', 'Orden cerrada exitosamente. Total: $686.00. Método de pago: Efectivo. Dinero recibido: $700.00. Cambio: $14.00. Mesa: A-1', 1, '2025-11-21 00:22:34'),
(18, 12, 'ORDEN_CERRADA', 'Orden cerrada exitosamente. Total: $350.00. Método de pago: Efectivo. Dinero recibido: $400.00. Cambio: $50.00. Mesa: A-1', 1, '2025-11-21 00:40:14'),
(19, 13, 'ORDEN_CERRADA', 'Orden cerrada exitosamente. Total: $697.00. Método de pago: Transferencia. Mesa: A-1', 1, '2025-11-21 00:47:08'),
(20, 14, 'ORDEN_CERRADA', 'Orden cerrada exitosamente. Total: $716.00. Método de pago: Crédito. Mesa: A-2', 1, '2025-11-21 00:49:24'),
(21, 15, 'ORDEN_CERRADA', 'Orden cerrada exitosamente. Total: $347.00. Método de pago: Débito. Mesa: A-1', 2, '2025-11-21 01:27:40'),
(22, 16, 'ORDEN_CERRADA', 'Orden cerrada exitosamente. Total: $324.00. Método de pago: Efectivo. Dinero recibido: $324.00. Pago exacto. Mesa: A-1', 1, '2025-11-21 05:35:01');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mesas`
--

CREATE TABLE `mesas` (
  `id` int NOT NULL,
  `nombre` varchar(50) CHARACTER SET utf32 COLLATE utf32_spanish_ci NOT NULL,
  `estado` varchar(20) CHARACTER SET utf32 COLLATE utf32_spanish_ci DEFAULT 'abierta'
) ENGINE=InnoDB DEFAULT CHARSET=utf32 COLLATE=utf32_spanish_ci;

--
-- Volcado de datos para la tabla `mesas`
--

INSERT INTO `mesas` (`id`, `nombre`, `estado`) VALUES
(13, 'A-1', 'disponible'),
(14, 'A-2', 'disponible'),
(15, 'A-3', 'disponible'),
(16, 'A-4', 'disponible'),
(18, 'B-1', 'disponible'),
(19, 'B-2', 'disponible'),
(20, 'B-3', 'abierta'),
(21, 'C-1', 'abierta'),
(22, 'C-2', 'disponible'),
(23, 'C-3', 'abierta'),
(24, 'D-1', 'disponible'),
(25, 'D-2', 'abierta'),
(26, 'D-3', 'abierta'),
(27, 'D-4', 'abierta');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mesa_layouts`
--

CREATE TABLE `mesa_layouts` (
  `id` int NOT NULL,
  `mesa_id` int DEFAULT NULL,
  `posicion_x` int DEFAULT '0',
  `posicion_y` int DEFAULT '0',
  `ancho` int DEFAULT '80',
  `alto` int DEFAULT '80',
  `rotacion` int DEFAULT '0',
  `tipo_visual` varchar(20) COLLATE utf32_spanish_ci DEFAULT 'square'
) ENGINE=InnoDB DEFAULT CHARSET=utf32 COLLATE=utf32_spanish_ci;

--
-- Volcado de datos para la tabla `mesa_layouts`
--

INSERT INTO `mesa_layouts` (`id`, `mesa_id`, `posicion_x`, `posicion_y`, `ancho`, `alto`, `rotacion`, `tipo_visual`) VALUES
(1, 18, 58, 220, 182, 140, 0, 'rectangular'),
(2, 19, 322, 221, 177, 141, 0, 'rectangular'),
(13, 14, 319, 22, 182, 137, 0, 'rectangular'),
(26, 15, 582, 19, 178, 137, 0, 'rectangular'),
(28, 16, 843, 19, 179, 138, 0, 'rectangular'),
(29, 13, 58, 22, 182, 136, 0, 'rectangular'),
(136, 20, 583, 223, 180, 139, 0, 'rectangular'),
(139, 21, 57, 423, 184, 142, 0, 'rectangular'),
(140, 22, 321, 421, 182, 145, 0, 'rectangular'),
(141, 23, 579, 420, 186, 145, 0, 'rectangular'),
(148, 24, 57, 642, 185, 140, 0, 'rectangular'),
(149, 25, 320, 641, 185, 142, 0, 'rectangular'),
(150, 26, 579, 639, 185, 138, 0, 'rectangular'),
(151, 27, 839, 640, 184, 141, 0, 'rectangular');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `notificaciones_log`
--

CREATE TABLE `notificaciones_log` (
  `id` int NOT NULL,
  `destino` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Número de teléfono o email destino',
  `mensaje` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Contenido del mensaje enviado',
  `tipo` enum('SMS','EMAIL_FALLBACK','SMS_PRUEBA','EMAIL_PIN','EMAIL_PRUEBA') COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Tipo de notificación',
  `estado` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Estado del envío (EXITOSO, ERROR, etc.)',
  `fecha_envio` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha y hora del envío'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `notificaciones_log`
--

INSERT INTO `notificaciones_log` (`id`, `destino`, `mensaje`, `tipo`, `estado`, `fecha_envio`) VALUES
(1, 'cencarnacion@kallijaguar-inventory.com', '🚨 AUTORIZACIÓN REQUERIDA - Cancelación de Producto #4', 'EMAIL_PIN', 'EXITOSO', '2025-11-20 00:22:29'),
(2, 'temoc612@gmail.com', '🚨 AUTORIZACIÓN REQUERIDA - Cancelación de Producto #4', 'EMAIL_PIN', 'EXITOSO', '2025-11-20 00:22:31'),
(3, 'cencarnacion@kallijaguar-inventory.com', '🚨 AUTORIZACIÓN REQUERIDA - Cancelación de Producto #7', 'EMAIL_PIN', 'EXITOSO', '2025-11-20 18:14:59'),
(4, 'temoc612@gmail.com', '🚨 AUTORIZACIÓN REQUERIDA - Cancelación de Producto #7', 'EMAIL_PIN', 'EXITOSO', '2025-11-20 18:15:00'),
(5, 'cencarnacion@kallijaguar-inventory.com', '🚨 AUTORIZACIÓN REQUERIDA - Cancelación de Producto #7', 'EMAIL_PIN', 'EXITOSO', '2025-11-20 18:48:06'),
(6, 'temoc612@gmail.com', '🚨 AUTORIZACIÓN REQUERIDA - Cancelación de Producto #7', 'EMAIL_PIN', 'EXITOSO', '2025-11-20 18:48:07'),
(7, 'cencarnacion@kallijaguar-inventory.com', '🚨 AUTORIZACIÓN REQUERIDA - Cancelación de Producto #7', 'EMAIL_PIN', 'EXITOSO', '2025-11-20 19:10:42'),
(8, 'temoc612@gmail.com', '🚨 AUTORIZACIÓN REQUERIDA - Cancelación de Producto #7', 'EMAIL_PIN', 'EXITOSO', '2025-11-20 19:10:43'),
(9, 'cencarnacion@kallijaguar-inventory.com', '🚨 AUTORIZACIÓN REQUERIDA - Cancelación de Producto #7', 'EMAIL_PIN', 'EXITOSO', '2025-11-20 19:25:50'),
(10, 'temoc612@gmail.com', '🚨 AUTORIZACIÓN REQUERIDA - Cancelación de Producto #7', 'EMAIL_PIN', 'EXITOSO', '2025-11-20 19:25:51'),
(11, 'cencarnacion@kallijaguar-inventory.com', '🚨 AUTORIZACIÓN REQUERIDA - Cancelación de Producto #8', 'EMAIL_PIN', 'EXITOSO', '2025-11-20 21:03:30'),
(12, 'temoc612@gmail.com', '🚨 AUTORIZACIÓN REQUERIDA - Cancelación de Producto #8', 'EMAIL_PIN', 'EXITOSO', '2025-11-20 21:03:31');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ordenes`
--

CREATE TABLE `ordenes` (
  `id` int NOT NULL,
  `codigo` varchar(32) CHARACTER SET utf32 COLLATE utf32_spanish_ci DEFAULT NULL,
  `mesa_id` int NOT NULL,
  `usuario_id` int DEFAULT NULL,
  `cerrada_por_usuario_id` int DEFAULT NULL,
  `estado` varchar(20) CHARACTER SET utf32 COLLATE utf32_spanish_ci DEFAULT 'abierta',
  `total` decimal(10,2) DEFAULT '0.00',
  `metodo_pago` enum('efectivo','debito','credito','transferencia') CHARACTER SET utf32 COLLATE utf32_spanish_ci DEFAULT 'efectivo',
  `creada_en` datetime DEFAULT CURRENT_TIMESTAMP,
  `cerrada_en` timestamp NULL DEFAULT NULL,
  `dinero_recibido` decimal(10,2) DEFAULT NULL COMMENT 'Dinero recibido cuando el pago es en efectivo',
  `cambio` decimal(10,2) DEFAULT NULL COMMENT 'Cambio entregado cuando el pago es en efectivo'
) ENGINE=InnoDB DEFAULT CHARSET=utf32 COLLATE=utf32_spanish_ci;

--
-- Volcado de datos para la tabla `ordenes`
--

INSERT INTO `ordenes` (`id`, `codigo`, `mesa_id`, `usuario_id`, `cerrada_por_usuario_id`, `estado`, `total`, `metodo_pago`, `creada_en`, `cerrada_en`, `dinero_recibido`, `cambio`) VALUES
(1, 'ORD-20251119-C5F26A', 13, 2, NULL, 'cerrada', 921.00, 'efectivo', '2025-11-19 17:14:13', '2025-11-19 23:48:43', 1000.00, 79.00),
(2, 'ORD-20251119-0A5588', 13, 2, NULL, 'cerrada', 351.00, 'efectivo', '2025-11-19 18:05:47', '2025-11-20 00:21:17', 400.00, 49.00),
(3, 'ORD-20251119-DD7203', 14, 2, NULL, 'cerrada', 191.00, 'efectivo', '2025-11-19 18:06:08', '2025-11-20 00:20:19', 200.00, 9.00),
(4, 'ORD-20251119-029066', 15, 2, NULL, 'cerrada', 682.00, 'transferencia', '2025-11-19 18:06:48', '2025-11-20 01:55:11', NULL, NULL),
(5, 'ORD-20251119-69BA5C', 13, 1, NULL, 'cerrada', 897.00, 'credito', '2025-11-19 21:37:55', '2025-11-20 03:53:29', NULL, NULL),
(6, 'ORD-20251119-BAB591', 14, 1, NULL, 'cerrada', 769.00, 'debito', '2025-11-19 21:38:12', '2025-11-20 03:53:36', NULL, NULL),
(7, 'ORD-20251119-470A76', 13, 1, NULL, 'cerrada', 1570.00, 'efectivo', '2025-11-19 22:18:01', '2025-11-20 20:54:05', 2000.00, 430.00),
(8, 'ORD-20251120-C0F64D', 13, 1, NULL, 'cerrada', 230.00, 'debito', '2025-11-20 14:55:30', '2025-11-20 21:11:05', NULL, NULL),
(9, 'ORD-20251120-EDAADD', 13, 1, NULL, 'cerrada', 167.00, 'efectivo', '2025-11-20 15:19:03', '2025-11-20 21:51:58', 200.00, 33.00),
(10, 'ORD-20251120-46BC4D', 13, 1, NULL, 'cerrada', 736.00, 'efectivo', '2025-11-20 18:03:29', '2025-11-21 00:07:04', 1000.00, 264.00),
(11, 'ORD-20251120-8DDFA6', 13, 1, NULL, 'cerrada', 686.00, 'efectivo', '2025-11-20 18:07:08', '2025-11-21 00:22:34', 700.00, 14.00),
(12, 'ORD-20251120-C289EA', 13, 1, NULL, 'cerrada', 350.00, 'efectivo', '2025-11-20 18:22:38', '2025-11-21 00:40:14', 400.00, 50.00),
(13, 'ORD-20251120-E7EDEF', 13, 1, NULL, 'cerrada', 697.00, 'transferencia', '2025-11-20 18:45:50', '2025-11-21 00:47:08', NULL, NULL),
(14, 'ORD-20251120-395A2F', 14, 1, NULL, 'cerrada', 716.00, 'credito', '2025-11-20 18:48:18', '2025-11-21 00:49:24', NULL, NULL),
(15, 'ORD-20251120-9EEF12', 13, 2, NULL, 'cerrada', 347.00, 'debito', '2025-11-20 19:24:03', '2025-11-21 01:27:40', NULL, NULL),
(16, 'ORD-20251120-DE02D3', 13, 1, NULL, 'cerrada', 324.00, 'efectivo', '2025-11-20 20:31:36', '2025-11-21 05:35:01', 324.00, 0.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `orden_productos`
--

CREATE TABLE `orden_productos` (
  `id` int NOT NULL,
  `orden_id` int NOT NULL,
  `producto_id` int NOT NULL,
  `item_index` int DEFAULT '1' COMMENT 'Índice para diferenciar múltiples instancias del mismo producto',
  `agregado_por_usuario_id` int DEFAULT NULL,
  `cantidad` int NOT NULL DEFAULT '1',
  `estado` varchar(20) CHARACTER SET utf32 COLLATE utf32_spanish_ci DEFAULT 'pendiente',
  `preparado` int NOT NULL DEFAULT '0',
  `preparado_por_usuario_id` int DEFAULT NULL,
  `cancelado` int NOT NULL DEFAULT '0',
  `cancelado_por_usuario_id` int DEFAULT NULL,
  `pendiente_cancelacion` int DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf32 COLLATE=utf32_spanish_ci;

--
-- Volcado de datos para la tabla `orden_productos`
--

INSERT INTO `orden_productos` (`id`, `orden_id`, `producto_id`, `item_index`, `agregado_por_usuario_id`, `cantidad`, `estado`, `preparado`, `preparado_por_usuario_id`, `cancelado`, `cancelado_por_usuario_id`, `pendiente_cancelacion`) VALUES
(1, 1, 11, 1, 2, 1, 'pendiente', 1, 1, 0, NULL, 0),
(2, 1, 66, 1, 2, 1, 'pendiente', 1, 1, 0, NULL, 0),
(3, 1, 15, 1, 2, 1, 'pendiente', 1, 1, 0, NULL, 0),
(4, 1, 56, 1, 2, 1, 'pendiente', 1, 1, 0, NULL, 0),
(5, 1, 19, 1, 2, 1, 'pendiente', 1, 1, 0, NULL, 0),
(6, 1, 32, 1, 2, 1, 'pendiente', 1, 1, 0, NULL, 0),
(7, 1, 8, 1, 2, 1, 'pendiente', 1, 1, 0, NULL, 0),
(8, 1, 7, 1, 2, 1, 'pendiente', 1, 1, 0, NULL, 0),
(9, 1, 24, 1, 2, 1, 'pendiente', 1, 1, 0, NULL, 0),
(10, 1, 81, 1, 2, 1, 'pendiente', 1, 1, 0, NULL, 0),
(11, 1, 14, 1, 2, 1, 'pendiente', 1, 1, 0, NULL, 0),
(12, 3, 11, 1, 2, 1, 'pendiente', 1, 1, 0, NULL, 0),
(13, 3, 15, 1, 2, 1, 'pendiente', 1, 1, 0, NULL, 0),
(14, 2, 11, 1, 2, 1, 'pendiente', 1, 1, 0, NULL, 0),
(15, 2, 7, 1, 2, 1, 'pendiente', 1, 1, 0, NULL, 0),
(16, 2, 19, 1, 2, 1, 'pendiente', 1, 1, 0, NULL, 0),
(17, 2, 15, 1, 2, 1, 'pendiente', 1, 1, 0, NULL, 0),
(18, 4, 56, 1, 2, 1, 'pendiente', 1, 1, 0, NULL, 0),
(19, 4, 54, 1, 2, 1, 'pendiente', 1, 1, 0, NULL, 0),
(20, 4, 51, 1, 2, 1, 'pendiente', 1, 1, 0, NULL, 0),
(21, 4, 61, 1, 2, 1, 'pendiente', 1, 1, 0, NULL, 0),
(22, 4, 14, 1, 2, 1, 'pendiente', 1, 1, 0, NULL, 0),
(23, 4, 67, 1, 2, 1, 'pendiente', 1, 1, 0, NULL, 0),
(24, 4, 68, 1, 2, 1, 'pendiente', 1, 1, 0, NULL, 0),
(25, 4, 15, 1, 2, 1, 'pendiente', 0, NULL, 1, NULL, 0),
(26, 4, 49, 1, 2, 1, 'pendiente', 1, 1, 0, NULL, 0),
(27, 5, 66, 1, 1, 1, 'pendiente', 1, 1, 0, NULL, 0),
(28, 5, 14, 1, 1, 1, 'pendiente', 1, 1, 0, NULL, 0),
(29, 5, 15, 1, 1, 1, 'pendiente', 1, 1, 0, NULL, 0),
(30, 5, 17, 1, 1, 1, 'pendiente', 1, 1, 0, NULL, 0),
(31, 5, 11, 1, 1, 1, 'pendiente', 1, 1, 0, NULL, 0),
(32, 5, 56, 1, 1, 1, 'pendiente', 1, 1, 0, NULL, 0),
(33, 5, 32, 1, 1, 1, 'pendiente', 1, 1, 0, NULL, 0),
(34, 6, 11, 1, 1, 1, 'pendiente', 1, 1, 0, NULL, 0),
(35, 6, 17, 1, 1, 1, 'pendiente', 1, 1, 0, NULL, 0),
(36, 6, 56, 1, 1, 1, 'pendiente', 1, 1, 0, NULL, 0),
(37, 6, 32, 1, 1, 1, 'pendiente', 1, 1, 0, NULL, 0),
(38, 7, 6, 1, 1, 1, 'pendiente', 0, NULL, 1, NULL, 0),
(39, 7, 11, 1, 1, 1, 'pendiente', 1, 1, 0, NULL, 0),
(40, 7, 15, 1, 1, 2, 'pendiente', 1, 1, 1, NULL, 0),
(41, 7, 19, 1, 1, 1, 'pendiente', 1, 1, 0, NULL, 0),
(42, 7, 6, 1, 1, 1, 'pendiente', 1, 1, 0, NULL, 0),
(43, 7, 8, 1, 1, 1, 'pendiente', 1, 1, 0, NULL, 0),
(44, 7, 51, 1, 1, 3, 'pendiente', 3, 1, 0, NULL, 0),
(45, 7, 49, 1, 1, 1, 'pendiente', 1, 1, 0, NULL, 0),
(46, 7, 18, 1, 1, 1, 'pendiente', 1, 1, 0, NULL, 0),
(47, 7, 55, 1, 1, 1, 'pendiente', 1, 1, 0, NULL, 0),
(48, 7, 54, 1, 1, 1, 'pendiente', 1, 1, 0, NULL, 0),
(49, 7, 52, 1, 1, 1, 'pendiente', 1, 1, 0, NULL, 0),
(50, 7, 50, 1, 1, 1, 'pendiente', 1, 1, 0, NULL, 0),
(51, 7, 53, 1, 1, 1, 'pendiente', 1, 1, 0, NULL, 0),
(52, 8, 52, 1, 1, 1, 'pendiente', 0, NULL, 1, NULL, 0),
(53, 8, 50, 1, 1, 1, 'pendiente', 1, 1, 0, NULL, 0),
(54, 8, 51, 1, 1, 1, 'pendiente', 1, 1, 0, NULL, 0),
(55, 9, 5, 1, 1, 1, 'pendiente', 1, 1, 0, NULL, 0),
(56, 9, 15, 1, 1, 1, 'pendiente', 1, 1, 0, NULL, 0),
(57, 10, 27, 1, 1, 1, 'pendiente', 1, 1, 0, NULL, 0),
(58, 10, 26, 1, 1, 1, 'pendiente', 1, 1, 0, NULL, 0),
(59, 10, 54, 1, 1, 1, 'pendiente', 1, 1, 0, NULL, 0),
(60, 10, 31, 1, 1, 1, 'pendiente', 1, 1, 0, NULL, 0),
(61, 10, 5, 1, 1, 1, 'pendiente', 1, 1, 0, NULL, 0),
(62, 10, 5, 1, 1, 1, 'pendiente', 1, 1, 0, NULL, 0),
(63, 10, 5, 1, 1, 1, 'pendiente', 1, 1, 0, NULL, 0),
(64, 11, 5, 1, 1, 1, 'pendiente', 1, 1, 0, NULL, 0),
(65, 11, 5, 1, 1, 1, 'pendiente', 1, 1, 0, NULL, 0),
(66, 11, 50, 1, 1, 1, 'pendiente', 1, 1, 0, NULL, 0),
(67, 11, 50, 1, 1, 1, 'pendiente', 1, 1, 0, NULL, 0),
(68, 11, 51, 1, 1, 1, 'pendiente', 1, 1, 0, NULL, 0),
(69, 11, 51, 2, 1, 1, 'pendiente', 1, 1, 0, NULL, 0),
(70, 12, 52, 1, 1, 1, 'pendiente', 1, 1, 0, NULL, 0),
(71, 12, 50, 1, 1, 1, 'pendiente', 1, 1, 0, NULL, 0),
(72, 12, 50, 2, 1, 1, 'pendiente', 1, 1, 0, NULL, 0),
(73, 13, 31, 1, 1, 2, 'pendiente', 2, 1, 0, NULL, 0),
(74, 13, 30, 1, 1, 1, 'pendiente', 1, 1, 0, NULL, 0),
(75, 13, 29, 1, 1, 1, 'pendiente', 1, 1, 0, NULL, 0),
(76, 13, 8, 1, 1, 1, 'pendiente', 1, 1, 0, NULL, 0),
(77, 13, 15, 1, 1, 1, 'pendiente', 1, 1, 0, NULL, 0),
(78, 13, 67, 1, 1, 1, 'pendiente', 1, 1, 0, NULL, 0),
(79, 14, 6, 1, 1, 1, 'pendiente', 1, 1, 0, NULL, 0),
(80, 14, 54, 1, 1, 1, 'pendiente', 1, 1, 0, NULL, 0),
(81, 14, 15, 1, 1, 2, 'pendiente', 2, 1, 0, NULL, 0),
(82, 14, 17, 1, 1, 1, 'pendiente', 1, 1, 0, NULL, 0),
(83, 15, 50, 1, 2, 1, 'pendiente', 1, 1, 0, NULL, 0),
(84, 15, 50, 2, 2, 1, 'pendiente', 1, 1, 0, NULL, 0),
(85, 15, 15, 1, 2, 1, 'pendiente', 1, 1, 0, NULL, 0),
(86, 15, 67, 1, 2, 1, 'pendiente', 1, 1, 0, NULL, 0),
(87, 16, 51, 1, 1, 1, 'pendiente', 1, 1, 0, NULL, 0),
(88, 16, 31, 1, 1, 1, 'pendiente', 1, 1, 0, NULL, 0),
(89, 16, 88, 1, 1, 1, 'pendiente', 1, 1, 0, NULL, 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `orden_producto_variedades`
--

CREATE TABLE `orden_producto_variedades` (
  `id` int NOT NULL,
  `orden_id` int NOT NULL,
  `producto_id` int NOT NULL,
  `item_index` int NOT NULL COMMENT 'Índice del producto en la orden (para múltiples del mismo producto)',
  `grupo_id` int DEFAULT NULL COMMENT 'Puede ser NULL si se borra el grupo',
  `opcion_id` int DEFAULT NULL COMMENT 'Puede ser NULL si se borra la opción',
  `grupo_nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Guardamos el nombre por si se borra el grupo',
  `opcion_nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Guardamos el nombre por si se borra la opción',
  `precio_adicional` decimal(10,2) DEFAULT '0.00',
  `fecha_creacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Variedades seleccionadas en cada producto de una orden';

--
-- Volcado de datos para la tabla `orden_producto_variedades`
--

INSERT INTO `orden_producto_variedades` (`id`, `orden_id`, `producto_id`, `item_index`, `grupo_id`, `opcion_id`, `grupo_nombre`, `opcion_nombre`, `precio_adicional`, `fecha_creacion`) VALUES
(1, 8, 50, 1, NULL, NULL, 'Seleccione la Carne:', 'Puerco', 0.00, '2025-11-20 21:06:53'),
(2, 8, 51, 1, NULL, NULL, 'Tipo de Carne:', 'Pollo', 0.00, '2025-11-20 21:07:55'),
(3, 9, 5, 1, NULL, NULL, 'Tipo', 'Mexicana', 0.00, '2025-11-20 21:39:56'),
(4, 10, 26, 1, 21, 45, 'Tipo de Carne', 'Cerdo', 0.00, '2025-11-21 00:04:07'),
(5, 10, 54, 1, 13, 27, 'Tipo de Carne', 'Pollo', 0.00, '2025-11-21 00:04:40'),
(7, 10, 5, 2, 22, 48, 'Tipo', 'Chorizo', 0.00, '2025-11-21 00:05:16'),
(8, 10, 5, 3, 22, 47, 'Tipo', 'Mexicana', 0.00, '2025-11-21 00:05:24'),
(9, 11, 5, 1, 22, 47, 'Tipo', 'Mexicana', 0.00, '2025-11-21 00:07:15'),
(10, 11, 5, 2, 22, 48, 'Tipo', 'Chorizo', 0.00, '2025-11-21 00:07:23'),
(11, 11, 50, 1, 8, 16, 'Tipo de Carne', 'Cerdo', 0.00, '2025-11-21 00:07:35'),
(12, 11, 50, 2, 8, 17, 'Tipo de Carne', 'Pollo', 0.00, '2025-11-21 00:07:38'),
(13, 11, 51, 1, 10, 20, 'Tipo de Carne', 'Cerdo', 0.00, '2025-11-21 00:15:31'),
(14, 11, 51, 2, 10, 21, 'Tipo de Carne', 'Pollo', 0.00, '2025-11-21 00:15:35'),
(15, 12, 50, 1, 8, 16, 'Tipo de Carne', 'Cerdo', 0.00, '2025-11-21 00:39:46'),
(16, 12, 50, 2, 8, 17, 'Tipo de Carne', 'Pollo', 0.00, '2025-11-21 00:39:50'),
(17, 13, 30, 1, 16, 34, 'Tipo de Salsa', 'Roja', 0.00, '2025-11-21 00:46:10'),
(18, 13, 29, 1, 17, 37, 'Tipo de Salsa', 'Verde', 0.00, '2025-11-21 00:46:15'),
(19, 14, 54, 1, 13, 27, 'Tipo de Carne', 'Pollo', 0.00, '2025-11-21 00:48:36'),
(20, 15, 50, 1, 8, 16, 'Tipo de Carne', 'Cerdo', 0.00, '2025-11-21 01:24:08'),
(21, 15, 50, 2, 8, 17, 'Tipo de Carne', 'Pollo', 0.00, '2025-11-21 01:24:11'),
(22, 16, 51, 1, 10, 20, 'Tipo de Carne', 'Cerdo', 0.00, '2025-11-21 02:32:04'),
(23, 16, 31, 1, 23, 50, 'Tipo de Salsa', 'Roja', 0.00, '2025-11-21 02:33:52');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `productos`
--

CREATE TABLE `productos` (
  `id` int NOT NULL,
  `nombre` varchar(100) CHARACTER SET utf32 COLLATE utf32_spanish_ci NOT NULL,
  `precio` decimal(10,2) NOT NULL,
  `descripcion` text CHARACTER SET utf32 COLLATE utf32_spanish_ci,
  `imagen` varchar(255) CHARACTER SET utf32 COLLATE utf32_spanish_ci DEFAULT NULL,
  `categoria` varchar(50) CHARACTER SET utf32 COLLATE utf32_spanish_ci NOT NULL DEFAULT 'comidas',
  `type` int NOT NULL,
  `tiene_variedades` tinyint(1) DEFAULT '0' COMMENT '1 = Producto con variedades, 0 = Producto simple'
) ENGINE=InnoDB DEFAULT CHARSET=utf32 COLLATE=utf32_spanish_ci;

--
-- Volcado de datos para la tabla `productos`
--

INSERT INTO `productos` (`id`, `nombre`, `precio`, `descripcion`, `imagen`, `categoria`, `type`, `tiene_variedades`) VALUES
(5, 'Huevos al Gusto', 113.00, 'Jamón, chorizo, a la mexicana, rancheros, salchicha. (Descripción actualizada)', '691fa83523b3b.png', 'comidas', 1, 1),
(6, 'Chalupas Chilapeñas', 99.00, 'Orden de 5 piezas.', '68b5e0f58d73a.jpg', 'comidas', 2, 0),
(7, 'Chiles Capones', 95.00, '3 chiles jalapeños, requesón y un toque de crema.', '68b5e10446e1a.png', 'comidas', 7, 0),
(8, 'Chilate 500 ml', 65.00, '', '68b5e11707a2f.png', 'bebidas', 8, 0),
(10, 'Socorrido Jaguar', 120.00, 'Huevo frito en salsa roja, verde o jitomate con un toque de epazote y acompañados de frijoles.', '68b5e1987b8df.png', 'comidas', 1, 1),
(11, 'Aporreadillo Guerrerense', 132.00, 'Huevos en salsa de jitomate, epazote y cecina, acompañados de frijoles de la casa.', '691e2c8e71311.png', 'comidas', 1, 0),
(12, 'Vaso Agua Fresca 500ml', 48.00, '', '687ac619b4bb8.png', 'bebidas', 8, 0),
(13, 'Yoli', 55.00, '', '687186705a13b.png', 'bebidas', 8, 0),
(14, 'Cerveza Corona', 54.00, '', '691fda7782c6f.png', 'bebidas', 12, 0),
(15, 'Cerveza Victoria', 54.00, '', '691fdabef35d3.png', 'bebidas', 12, 0),
(16, 'Orden de Pata en Escabeche', 109.00, '', '691e308a7d1c3.png', 'comidas', 7, 0),
(17, 'Botana Casa Jaguar', 379.00, 'Plato especial con: 2 chalupas, 2 tostadas de pollo, 2 tacos dorados,2 picaditas, 2 chiles capones y 1 patita en escabeche', '68b5e1aee65c9.png', 'comidas', 7, 0),
(18, 'Pozole Verde Chico', 115.00, '', '6871843311658.png', 'comidas', 10, 1),
(19, 'Cheese Cake', 65.00, '', '6872dbbc039e5.png', 'bebidas', 11, 0),
(20, 'Flan Napolitano', 65.00, '', '6872dc144a247.png', 'bebidas', 11, 0),
(21, 'Jugo de Naranja', 65.00, '', '691e2dc738db3.png', 'bebidas', 8, 0),
(22, 'Torreja', 65.00, '', '6878613a00e83.png', 'bebidas', 11, 0),
(23, 'Pieza Chalupa Chilapeña', 20.00, '', '687866ceefec2.jpg', 'comidas', 2, 0),
(24, 'Enchiladas Jaguar', 109.00, '', '6878672f13cb7.png', 'comidas', 2, 0),
(25, 'Tostadas Chilapeñas', 120.00, '', '687867e192b4f.png', 'comidas', 2, 1),
(26, 'Pieza Tostada Chilapeña', 40.00, '', '687869913b81f.png', 'comidas', 2, 1),
(27, 'Picaditas Sencillas', 92.00, '', '68786cd1586a8.jpg', 'comidas', 2, 0),
(28, 'Huevos Tirados', 132.00, 'Fritos, revueltos con frijoles, chorizo, queso y chile seco.', '687ab8e090bed.png', 'comidas', 1, 0),
(29, 'Chilaquiles Naturales', 105.00, 'Totopos ahogados en salsa verde o roja con un toque de epazote, servidos con crema, queso, cebolla y aguacate.', '687ab97aa277b.png', 'comidas', 1, 1),
(30, 'Chilaquiles Huevo', 130.00, 'Totopos ahogados en salsa verde o roja con un toque de epazote, servidos con crema, queso, cebolla y aguacate.', '687ab98da3dc8.png', 'comidas', 1, 1),
(31, 'Chilaquiles Pollo', 135.00, 'Totopos ahogados en salsa verde o roja con un toque de epazote, servidos con crema, queso, cebolla y aguacate.', '691fa7c6cf93e.png', 'comidas', 1, 1),
(32, 'Chilaquiles Guerrerenses', 140.00, 'Totopos ahogados en salsa de jitomate, epazote, servidos con crema y queso, cebolla, aguacate y acompañados de 110g de cecina.', '687aba112a8dd.png', 'comidas', 1, 0),
(33, 'Pieza Enchilada Jaguar', 36.00, '', '691e954f980ce.png', 'comidas', 2, 0),
(34, 'Pieza Picadita Sencillas', 29.00, '', '691e958439795.png', 'comidas', 2, 0),
(35, 'Picaditas con Carne', 109.00, 'Orden de 3 piezas.', '691e95ca75b2c.png', 'comidas', 2, 0),
(36, 'Pieza Picadita con Carne', 35.00, '', '691e95a50ceb5.png', 'comidas', 2, 0),
(37, 'Tacos Dorados', 120.00, 'Orden de 3 piezas y consomé.', '691e9396b51c1.png', 'comidas', 2, 0),
(38, 'Pieza Taco Dorado', 40.00, '', '691e95272d1ae.png', 'comidas', 2, 0),
(39, 'Gordita Casa Jaguar', 92.00, 'Rellena de pollo, chorizo o queso Oaxaca.', '687abcf8a08c5.png', 'comidas', 2, 0),
(40, 'Pieza Chile Capon', 35.00, '', '687abd2d8666d.png', 'comidas', 7, 0),
(42, 'Gordita Combinada', 103.00, 'Puede llevar dos ingredientes.', '691e94ff8311b.png', 'comidas', 2, 0),
(43, 'Quesadillas Jaguar', 109.00, 'De queso con pollo o chorizo. Orden de 3 piezas.', '691e941d882d5.png', 'comidas', 2, 0),
(44, 'Pieza Quesadilla Jaguar', 36.00, '', '691e943f0bbb5.png', 'comidas', 2, 0),
(45, 'Tostadas de Pata', 109.00, 'De res, orden de 3 piezas.', '691e94b374d17.png', 'comidas', 2, 0),
(46, 'Pieza de Tostada de Pata', 36.00, '', '691e94d2d1a3d.png', 'comidas', 2, 0),
(47, 'Sopes Chilapeños', 109.00, 'Picaditas fritas preparadas con frijoles de la casa, verdura, pollo, chipotle, queso y crema.', '691e93c88cf58.png', 'comidas', 2, 0),
(48, 'Pieza de Sope Chilapeño', 36.00, '', '691e93e9dac6f.png', 'comidas', 2, 0),
(49, 'Pozole Blanco Mini', 80.00, 'Incluye aguacate, chicharrón y tostada.', '687abfaf99a45.png', 'comidas', 10, 0),
(50, 'Pozole Blanco Chico', 110.00, 'Incluye aguacate, chicharrón y tostada.', '687abfd587a99.png', 'comidas', 10, 1),
(51, 'Pozole Blanco Mediano', 120.00, 'Incluye aguacate, chicharrón y tostada.', '687abfebd912a.png', 'comidas', 10, 1),
(52, 'Pozole Blanco Grande', 130.00, 'Incluye aguacate, chicharrón y tostada.', '687ac01704912.png', 'comidas', 10, 1),
(53, 'Pozole Verde Mini', 85.00, 'Incluye aguacate, chicharrón y tostada.', '691e315d000ed.png', 'comidas', 10, 0),
(54, 'Pozole Verde Mediano', 130.00, 'Incluye aguacate, chicharrón y tostada.', '687ac12eeb5be.png', 'comidas', 10, 1),
(55, 'Pozole Verde Grande', 140.00, 'Incluye aguacate, chicharrón y tostada.', '687ac153db632.png', 'comidas', 10, 1),
(56, 'Botanero de Pozole', 113.00, '1 orden de aguacate, 1 orden de chicharrón y 4 tostadas.', '687ac20b42147.png', 'comidas', 7, 0),
(57, 'Orden de Aguacate', 55.00, '', '687ac268bfa88.png', 'comidas', 7, 0),
(58, 'Orden de Tostadas', 20.00, '', '687ac28e85f48.png', 'comidas', 7, 0),
(59, 'Orden de Chicharrón', 65.00, '', '691e30eec0a29.png', 'comidas', 7, 0),
(60, 'Vaso Michelado', 32.00, '', '687ac4ea69182.png', 'bebidas', 12, 0),
(61, 'Vaso Chelado', 28.00, '', '687ac5150a775.png', 'bebidas', 12, 0),
(62, 'Vaso Clamato', 45.00, '', '687ac5efd7a9b.png', 'bebidas', 12, 0),
(63, 'Jarra Agua Fresca 1lt', 70.00, '', '687ac6393ff51.png', 'bebidas', 8, 0),
(64, 'Jarra Agua Fresca 1.8 Lt', 180.00, '', '687ac65e4bae1.png', 'bebidas', 8, 0),
(65, 'Jarra Chilate 1 Lt', 120.00, '', '687ac6bdde674.png', 'bebidas', 8, 0),
(66, 'Botella de Agua', 20.00, '', '691e35f1a82bf.png', 'bebidas', 8, 0),
(67, 'Shot de Mezcal Natural', 73.00, '', '687accf1ce8df.png', 'bebidas', 12, 0),
(68, 'Frapeado (Mezcal de Sabor)', 84.00, '', '687acc76c4a79.png', 'bebidas', 12, 0),
(69, 'Botella Mezcal Sabor', 300.00, 'Contenido neto 1 Lt.', '687ace07c959f.png', 'bebidas', 12, 0),
(70, 'Botella Mezcal Natural', 450.00, 'Contenido neto 750ml.', '691e95ff95be3.png', 'bebidas', 12, 0),
(71, 'Shot de Mezcal Sabor', 50.00, '', '687ad0a4e8cd2.png', 'bebidas', 12, 0),
(72, 'Chocolate en Agua', 62.00, '', '687ad50ab8e09.png', 'bebidas', 9, 0),
(73, 'Chocolate en Leche', 67.00, '', '691e946ed3d72.png', 'bebidas', 9, 0),
(74, 'Cafe', 45.00, '', '687ad58b80eb4.png', 'bebidas', 9, 0),
(75, 'Cafe Refill', 55.00, '', '687ad59da9d97.png', 'bebidas', 9, 0),
(76, 'Huevos Rancheros', 132.00, '', '691e3243dec38.png', 'comidas', 1, 0),
(77, 'Refresco', 45.00, '', NULL, 'bebidas', 8, 0),
(78, 'Jugo de Zanahoria', 62.00, '', '691e2de73c6ab.png', 'bebidas', 8, 0),
(79, 'Jugo Verde', 69.00, '', '691e2df827cd7.png', 'bebidas', 8, 0),
(80, 'Esquimo Vainilla', 69.00, '', '691e2ed0e2c4c.png', 'bebidas', 8, 0),
(81, 'Esquimo Chocolate', 69.00, '', '691e2ec3eab89.png', 'bebidas', 8, 0),
(82, 'Esquimo Fresa', 69.00, '', '691e2eb4eb449.png', 'bebidas', 8, 0),
(83, 'Esquimo Rompope', 69.00, '', '691e2ef6e2af6.png', 'bebidas', 8, 0),
(84, 'Limonada', 58.00, '', '691e2e2e0ca45.png', 'bebidas', 8, 0),
(85, 'Naranjada', 58.00, '', '691e2e36827b6.png', 'bebidas', 8, 0),
(86, 'Paquete Pozole Verde', 749.00, '', NULL, 'comidas', 10, 0),
(87, 'Paquete Pozole Blanco', 649.00, '', NULL, 'comidas', 10, 0),
(88, 'Paquete de Desayuno', 69.00, 'Todos los desayunos son acompañados con tortillas\r\nhechas a mano / Café + Jugo + Pan.', '691f8fef37df2.png', 'comidas', 1, 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `producto_variedad_grupos`
--

CREATE TABLE `producto_variedad_grupos` (
  `id` int NOT NULL,
  `producto_id` int NOT NULL,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Nombre del grupo (Ej: Salsa, Ingrediente)',
  `obligatorio` tinyint(1) DEFAULT '1' COMMENT '1 = Debe elegir una opción, 0 = Opcional',
  `orden` int DEFAULT '0' COMMENT 'Orden de visualización',
  `activo` tinyint(1) DEFAULT '1',
  `fecha_creacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Grupos de variedades de productos (Ej: Salsa, Ingrediente)';

--
-- Volcado de datos para la tabla `producto_variedad_grupos`
--

INSERT INTO `producto_variedad_grupos` (`id`, `producto_id`, `nombre`, `obligatorio`, `orden`, `activo`, `fecha_creacion`) VALUES
(8, 50, 'Tipo de Carne', 1, 1, 1, '2025-11-20 21:54:26'),
(9, 52, 'Tipo de Carne', 1, 1, 1, '2025-11-20 21:55:07'),
(10, 51, 'Tipo de Carne', 1, 1, 1, '2025-11-20 21:55:26'),
(11, 18, 'Tipo de Carne', 1, 1, 1, '2025-11-20 21:55:56'),
(12, 55, 'Tipo de Carne', 1, 1, 1, '2025-11-20 21:56:15'),
(13, 54, 'Tipo de Carne', 1, 1, 1, '2025-11-20 21:56:30'),
(16, 30, 'Tipo de Salsa', 1, 1, 1, '2025-11-20 21:58:59'),
(17, 29, 'Tipo de Salsa', 1, 1, 1, '2025-11-20 21:59:13'),
(19, 10, 'Tipo de Salsa', 1, 1, 1, '2025-11-20 23:07:48'),
(20, 25, 'Tipo de Carne', 1, 1, 1, '2025-11-20 23:09:19'),
(21, 26, 'Tipo de Carne', 1, 1, 1, '2025-11-20 23:09:58'),
(22, 5, 'Tipo', 1, 1, 1, '2025-11-20 23:45:57'),
(23, 31, 'Tipo de Salsa', 1, 1, 1, '2025-11-21 00:50:10');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `producto_variedad_opciones`
--

CREATE TABLE `producto_variedad_opciones` (
  `id` int NOT NULL,
  `grupo_id` int NOT NULL,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Nombre de la opción (Ej: Roja, Verde)',
  `precio_adicional` decimal(10,2) DEFAULT '0.00' COMMENT 'Precio extra por esta opción',
  `orden` int DEFAULT '0' COMMENT 'Orden de visualización',
  `activo` tinyint(1) DEFAULT '1',
  `fecha_creacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Opciones de cada grupo de variedad';

--
-- Volcado de datos para la tabla `producto_variedad_opciones`
--

INSERT INTO `producto_variedad_opciones` (`id`, `grupo_id`, `nombre`, `precio_adicional`, `orden`, `activo`, `fecha_creacion`) VALUES
(16, 8, 'Cerdo', 0.00, 1, 1, '2025-11-20 21:54:26'),
(17, 8, 'Pollo', 0.00, 2, 1, '2025-11-20 21:54:26'),
(18, 9, 'Cerdo', 0.00, 1, 1, '2025-11-20 21:55:07'),
(19, 9, 'Pollo', 0.00, 2, 1, '2025-11-20 21:55:07'),
(20, 10, 'Cerdo', 0.00, 1, 1, '2025-11-20 21:55:26'),
(21, 10, 'Pollo', 0.00, 2, 1, '2025-11-20 21:55:26'),
(22, 11, 'Cerdo', 0.00, 1, 1, '2025-11-20 21:55:56'),
(23, 11, 'Pollo', 0.00, 2, 1, '2025-11-20 21:55:56'),
(24, 12, 'Cerdo', 0.00, 1, 1, '2025-11-20 21:56:15'),
(25, 12, 'Pollo', 0.00, 2, 1, '2025-11-20 21:56:15'),
(26, 13, 'Cerdo', 0.00, 1, 1, '2025-11-20 21:56:30'),
(27, 13, 'Pollo', 0.00, 2, 1, '2025-11-20 21:56:30'),
(34, 16, 'Roja', 0.00, 1, 1, '2025-11-20 21:58:59'),
(35, 16, 'Verde', 0.00, 2, 1, '2025-11-20 21:58:59'),
(36, 17, 'Roja', 0.00, 1, 1, '2025-11-20 21:59:13'),
(37, 17, 'Verde', 0.00, 2, 1, '2025-11-20 21:59:13'),
(40, 19, 'Roja', 0.00, 1, 1, '2025-11-20 23:07:48'),
(41, 19, 'Verde', 0.00, 2, 1, '2025-11-20 23:07:48'),
(42, 19, 'Jitomate', 0.00, 3, 1, '2025-11-20 23:07:48'),
(43, 20, 'Cerdo', 0.00, 1, 1, '2025-11-20 23:09:19'),
(44, 20, 'Pollo', 0.00, 2, 1, '2025-11-20 23:09:19'),
(45, 21, 'Cerdo', 0.00, 1, 1, '2025-11-20 23:09:58'),
(46, 21, 'Pollo', 0.00, 2, 1, '2025-11-20 23:09:58'),
(47, 22, 'Mexicana', 0.00, 1, 1, '2025-11-20 23:45:57'),
(48, 22, 'Chorizo', 0.00, 2, 1, '2025-11-20 23:45:57'),
(49, 22, 'Jamon', 0.00, 3, 1, '2025-11-20 23:45:57'),
(50, 23, 'Roja', 0.00, 1, 1, '2025-11-21 00:50:10'),
(51, 23, 'Verde', 0.00, 2, 1, '2025-11-21 00:50:10');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--

CREATE TABLE `roles` (
  `id` int NOT NULL,
  `nombre` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci,
  `permisos` json DEFAULT NULL,
  `creado_en` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `roles`
--

INSERT INTO `roles` (`id`, `nombre`, `descripcion`, `permisos`, `creado_en`) VALUES
(1, 'administrador', 'Administrador del sistema', '{\"bar\": [\"ver\", \"marcar_preparado\"], \"mesas\": [\"crear\", \"editar\", \"eliminar\", \"ver\"], \"cocina\": [\"ver\", \"marcar_preparado\"], \"ordenes\": [\"crear\", \"editar\", \"eliminar\", \"ver\", \"cerrar\"], \"reportes\": [\"ver\", \"exportar\"], \"usuarios\": [\"crear\", \"editar\", \"eliminar\", \"ver\"], \"productos\": [\"crear\", \"editar\", \"eliminar\", \"ver\"], \"configuracion\": [\"ver\", \"editar\"]}', '2025-09-01 18:39:25'),
(2, 'mesero', 'Mesero del restaurante', '{\"mesas\": [\"ver\", \"editar\"], \"ordenes\": [\"crear\", \"editar\", \"ver\", \"cerrar\"], \"productos\": [\"ver\"]}', '2025-09-01 18:39:25'),
(3, 'cocinero', 'Personal de cocina', '{\"cocina\": [\"ver\", \"marcar_preparado\"], \"ordenes\": [\"ver\"]}', '2025-09-01 18:39:25'),
(4, 'bartender', 'Personal de bar', '{\"bar\": [\"ver\", \"marcar_preparado\"], \"ordenes\": [\"ver\"]}', '2025-09-01 18:39:25'),
(5, 'cajero', 'Personal de caja', '{\"ordenes\": [\"ver\", \"cerrar\"], \"reportes\": [\"ver\"]}', '2025-09-01 18:39:25');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sesiones`
--

CREATE TABLE `sesiones` (
  `id` int NOT NULL,
  `usuario_id` int NOT NULL,
  `token_jti` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `expires_at` timestamp NOT NULL,
  `revocado` tinyint(1) DEFAULT '0',
  `creado_en` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `sesiones`
--

INSERT INTO `sesiones` (`id`, `usuario_id`, `token_jti`, `ip_address`, `user_agent`, `expires_at`, `revocado`, `creado_en`) VALUES
(1, 2, 'e7e168590fe81901adb5c16c85ccc6b7', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-20 07:14:04', 0, '2025-11-19 23:14:04'),
(2, 1, '38c01b6171acb1f777bf8c7b08ca42a9', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.6 Safari/605.1.15', '2025-11-20 07:17:59', 0, '2025-11-19 23:17:59'),
(3, 1, '800e37c9fe954e4b0b8dcfbef7c09d36', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.6 Safari/605.1.15', '2025-11-20 07:45:38', 0, '2025-11-19 23:45:38'),
(4, 1, '55f0408625786f880aa7213bb3c0caee', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-20 08:42:12', 0, '2025-11-20 00:42:12'),
(5, 2, 'ae5b114328d14570e753d3c445cafeed', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.6 Safari/605.1.15', '2025-11-20 08:45:23', 0, '2025-11-20 00:45:23'),
(6, 2, '4b94b37d322a13f925ca7a7616ec4924', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.6 Safari/605.1.15', '2025-11-20 08:45:31', 0, '2025-11-20 00:45:31'),
(7, 2, '165edc1bae223f76845b5222598ea037', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.6 Safari/605.1.15', '2025-11-20 08:45:46', 0, '2025-11-20 00:45:46'),
(8, 2, '7b9d3e9174296cf385fcbcf537a64fbc', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.6 Safari/605.1.15', '2025-11-20 08:47:45', 0, '2025-11-20 00:47:45'),
(9, 2, 'b400c01aa76309f25b2adfa2717d94bc', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.6 Safari/605.1.15', '2025-11-20 08:47:52', 0, '2025-11-20 00:47:52'),
(10, 2, '4b3e2e43476186247feb3de190cba749', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.6 Safari/605.1.15', '2025-11-20 08:51:26', 0, '2025-11-20 00:51:26'),
(11, 1, '8b7b651df6fef089cd11d94861c84858', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-20 08:51:51', 0, '2025-11-20 00:51:51'),
(12, 2, '9ea2fe075a433e3524968e73c34ad415', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.6 Safari/605.1.15', '2025-11-20 08:52:09', 0, '2025-11-20 00:52:09'),
(13, 2, 'fde6c35a9437074ec5b5b3b2946add0f', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-20 08:52:30', 0, '2025-11-20 00:52:30'),
(14, 1, 'ac3823e0e30202f458a7789028df642e', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-20 09:00:44', 0, '2025-11-20 01:00:44'),
(15, 1, '194c8d3f6c113a0f97f069f75e50ef42', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-21 05:01:47', 0, '2025-11-20 21:01:47'),
(16, 2, '40789bcab091c42e5a5d8ba9f21da3a6', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-21 09:23:59', 0, '2025-11-21 01:23:59'),
(17, 1, 'f4a4c9d8020b9496c8c5bdcadc139e27', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.6 Safari/605.1.15', '2025-11-21 09:25:03', 0, '2025-11-21 01:25:03'),
(18, 1, '1dafdde7ea1b0c0fabf312eceaeb6d0f', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.6 Safari/605.1.15', '2025-11-21 09:25:15', 0, '2025-11-21 01:25:15'),
(19, 1, '0825549d7b47bf76054b3de5d4c2657f', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-21 09:25:44', 0, '2025-11-21 01:25:44'),
(20, 1, '6b750f39719af1e0d28cc83cdbf92b51', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-21 09:47:52', 0, '2025-11-21 01:47:52'),
(21, 1, '1d22fd471565e630c7c776f1fdd79212', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-21 09:52:36', 0, '2025-11-21 01:52:36'),
(22, 1, 'a95b88fa2e5dbe80815a5dada80c77ba', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-21 09:54:05', 0, '2025-11-21 01:54:05'),
(23, 1, '5f596ad44740b42cf538bcbe782d5256', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-21 10:10:06', 0, '2025-11-21 02:10:06');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `type`
--

CREATE TABLE `type` (
  `id` int NOT NULL,
  `nombre` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

--
-- Volcado de datos para la tabla `type`
--

INSERT INTO `type` (`id`, `nombre`) VALUES
(1, 'Desayunos'),
(2, 'Antojitos'),
(7, 'Extras'),
(8, 'Bebidas Frías'),
(9, 'Bebidas Calientes'),
(10, 'Pozole'),
(11, 'Postres'),
(12, 'Bebidas Espirituosas');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int NOT NULL,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombre_completo` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `rol_id` int NOT NULL,
  `activo` tinyint(1) DEFAULT '1',
  `ultimo_login` timestamp NULL DEFAULT NULL,
  `token_reset` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `token_reset_expira` timestamp NULL DEFAULT NULL,
  `creado_en` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `actualizado_en` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `username`, `email`, `password_hash`, `nombre_completo`, `rol_id`, `activo`, `ultimo_login`, `token_reset`, `token_reset_expira`, `creado_en`, `actualizado_en`) VALUES
(1, 'admin', 'admin@kallijaguar.com', '$2y$10$3b.W8ZgcW4iiF3oblFwmbuSDk1FySCOnq.MkZJje9IHlMoaUpWwZi', 'Cuauhtemoc', 1, 1, '2025-11-21 02:10:06', NULL, NULL, '2025-09-01 18:39:25', '2025-11-21 02:10:06'),
(2, 'mesero1', 'mesero1@kallijaguar.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Juan Pérez', 2, 1, '2025-11-21 01:23:59', NULL, NULL, '2025-09-01 18:39:25', '2025-11-21 01:23:59'),
(4, 'cocinero1', 'cocinero1@kallijaguar.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Carlos López', 3, 1, '2025-09-03 03:20:04', NULL, NULL, '2025-09-01 18:39:25', '2025-09-03 03:20:04'),
(5, 'bartender1', 'bartender1@kallijaguar.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Ana Martínez', 4, 1, '2025-09-03 03:20:34', NULL, NULL, '2025-09-01 19:07:06', '2025-09-03 03:20:34'),
(6, 'cajero1', 'cajero1@kallijaguar.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Pedro Sánchez', 5, 1, '2025-09-27 17:44:08', NULL, NULL, '2025-09-01 19:07:06', '2025-09-27 17:44:08');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `codigos_cancelacion`
--
ALTER TABLE `codigos_cancelacion`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_codigo` (`codigo`),
  ADD KEY `idx_usado_expiracion` (`usado`,`fecha_expiracion`);

--
-- Indices de la tabla `configuracion`
--
ALTER TABLE `configuracion`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `clave` (`clave`);

--
-- Indices de la tabla `historial_ordenes`
--
ALTER TABLE `historial_ordenes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_order_id` (`orden_id`);

--
-- Indices de la tabla `mesas`
--
ALTER TABLE `mesas`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `mesa_layouts`
--
ALTER TABLE `mesa_layouts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `mesa_id` (`mesa_id`);

--
-- Indices de la tabla `notificaciones_log`
--
ALTER TABLE `notificaciones_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_destino` (`destino`),
  ADD KEY `idx_fecha` (`fecha_envio`),
  ADD KEY `idx_tipo` (`tipo`);

--
-- Indices de la tabla `ordenes`
--
ALTER TABLE `ordenes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`),
  ADD KEY `mesa_id` (`mesa_id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `cerrada_por_usuario_id` (`cerrada_por_usuario_id`),
  ADD KEY `idx_metodo_pago` (`metodo_pago`);

--
-- Indices de la tabla `orden_productos`
--
ALTER TABLE `orden_productos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `orden_id` (`orden_id`),
  ADD KEY `producto_id` (`producto_id`),
  ADD KEY `agregado_por_usuario_id` (`agregado_por_usuario_id`),
  ADD KEY `preparado_por_usuario_id` (`preparado_por_usuario_id`),
  ADD KEY `cancelado_por_usuario_id` (`cancelado_por_usuario_id`);

--
-- Indices de la tabla `orden_producto_variedades`
--
ALTER TABLE `orden_producto_variedades`
  ADD PRIMARY KEY (`id`),
  ADD KEY `grupo_id` (`grupo_id`),
  ADD KEY `opcion_id` (`opcion_id`),
  ADD KEY `idx_orden` (`orden_id`),
  ADD KEY `idx_producto` (`producto_id`),
  ADD KEY `idx_orden_producto` (`orden_id`,`producto_id`,`item_index`);

--
-- Indices de la tabla `productos`
--
ALTER TABLE `productos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_type` (`type`),
  ADD KEY `idx_tiene_variedades` (`tiene_variedades`);

--
-- Indices de la tabla `producto_variedad_grupos`
--
ALTER TABLE `producto_variedad_grupos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_producto` (`producto_id`),
  ADD KEY `idx_activo` (`activo`);

--
-- Indices de la tabla `producto_variedad_opciones`
--
ALTER TABLE `producto_variedad_opciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_grupo` (`grupo_id`),
  ADD KEY `idx_activo` (`activo`);

--
-- Indices de la tabla `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `sesiones`
--
ALTER TABLE `sesiones`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token_jti` (`token_jti`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `expires_at` (`expires_at`);

--
-- Indices de la tabla `type`
--
ALTER TABLE `type`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `rol_id` (`rol_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `codigos_cancelacion`
--
ALTER TABLE `codigos_cancelacion`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `configuracion`
--
ALTER TABLE `configuracion`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=425;

--
-- AUTO_INCREMENT de la tabla `historial_ordenes`
--
ALTER TABLE `historial_ordenes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT de la tabla `mesas`
--
ALTER TABLE `mesas`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT de la tabla `mesa_layouts`
--
ALTER TABLE `mesa_layouts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=191;

--
-- AUTO_INCREMENT de la tabla `notificaciones_log`
--
ALTER TABLE `notificaciones_log`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `ordenes`
--
ALTER TABLE `ordenes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT de la tabla `orden_productos`
--
ALTER TABLE `orden_productos`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=90;

--
-- AUTO_INCREMENT de la tabla `orden_producto_variedades`
--
ALTER TABLE `orden_producto_variedades`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT de la tabla `productos`
--
ALTER TABLE `productos`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=89;

--
-- AUTO_INCREMENT de la tabla `producto_variedad_grupos`
--
ALTER TABLE `producto_variedad_grupos`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT de la tabla `producto_variedad_opciones`
--
ALTER TABLE `producto_variedad_opciones`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `sesiones`
--
ALTER TABLE `sesiones`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT de la tabla `type`
--
ALTER TABLE `type`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `historial_ordenes`
--
ALTER TABLE `historial_ordenes`
  ADD CONSTRAINT `fk_order_id` FOREIGN KEY (`orden_id`) REFERENCES `ordenes` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

--
-- Filtros para la tabla `mesa_layouts`
--
ALTER TABLE `mesa_layouts`
  ADD CONSTRAINT `mesa_layouts_ibfk_1` FOREIGN KEY (`mesa_id`) REFERENCES `mesas` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `ordenes`
--
ALTER TABLE `ordenes`
  ADD CONSTRAINT `fk_orden_cerrada_usuario` FOREIGN KEY (`cerrada_por_usuario_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `fk_orden_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `ordenes_ibfk_1` FOREIGN KEY (`mesa_id`) REFERENCES `mesas` (`id`);

--
-- Filtros para la tabla `orden_productos`
--
ALTER TABLE `orden_productos`
  ADD CONSTRAINT `fk_op_agregado_usuario` FOREIGN KEY (`agregado_por_usuario_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `fk_op_cancelado_usuario` FOREIGN KEY (`cancelado_por_usuario_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `fk_op_preparado_usuario` FOREIGN KEY (`preparado_por_usuario_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `orden_productos_ibfk_1` FOREIGN KEY (`orden_id`) REFERENCES `ordenes` (`id`),
  ADD CONSTRAINT `orden_productos_ibfk_2` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`);

--
-- Filtros para la tabla `orden_producto_variedades`
--
ALTER TABLE `orden_producto_variedades`
  ADD CONSTRAINT `orden_producto_variedades_ibfk_1` FOREIGN KEY (`orden_id`) REFERENCES `ordenes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `orden_producto_variedades_ibfk_2` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `orden_producto_variedades_ibfk_3` FOREIGN KEY (`grupo_id`) REFERENCES `producto_variedad_grupos` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `orden_producto_variedades_ibfk_4` FOREIGN KEY (`opcion_id`) REFERENCES `producto_variedad_opciones` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `productos`
--
ALTER TABLE `productos`
  ADD CONSTRAINT `fk_type` FOREIGN KEY (`type`) REFERENCES `type` (`id`);

--
-- Filtros para la tabla `producto_variedad_grupos`
--
ALTER TABLE `producto_variedad_grupos`
  ADD CONSTRAINT `producto_variedad_grupos_ibfk_1` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `producto_variedad_opciones`
--
ALTER TABLE `producto_variedad_opciones`
  ADD CONSTRAINT `producto_variedad_opciones_ibfk_1` FOREIGN KEY (`grupo_id`) REFERENCES `producto_variedad_grupos` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `sesiones`
--
ALTER TABLE `sesiones`
  ADD CONSTRAINT `sesiones_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`rol_id`) REFERENCES `roles` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
