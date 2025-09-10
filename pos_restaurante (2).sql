-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 27-08-2025 a las 21:08:15
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `pos_restaurante`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `caja`
--

CREATE TABLE `caja` (
  `id` int(11) NOT NULL,
  `cajero_id` int(11) NOT NULL,
  `sucursal_id` int(11) NOT NULL,
  `fecha_apertura` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_cierre` timestamp NULL DEFAULT NULL,
  `total_ingresos` decimal(10,2) DEFAULT 0.00,
  `total_gastos` decimal(10,2) DEFAULT 0.00,
  `saldo_final` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `caja`
--

INSERT INTO `caja` (`id`, `cajero_id`, `sucursal_id`, `fecha_apertura`, `fecha_cierre`, `total_ingresos`, `total_gastos`, `saldo_final`) VALUES
(1, 4, 1, '2025-08-22 16:57:01', NULL, 51.75, 80.00, -28.25),
(2, 4, 1, '2025-08-22 16:57:01', NULL, 18.00, 0.00, 18.00),
(3, 4, 2, '2025-08-22 16:57:01', NULL, 0.00, 15.00, -15.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_pedido`
--

CREATE TABLE `detalle_pedido` (
  `id` int(11) NOT NULL,
  `pedido_id` int(11) NOT NULL,
  `plato_id` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL DEFAULT 1,
  `descripcion` text DEFAULT NULL,
  `estado` enum('pendiente','cocinando','listo') DEFAULT 'pendiente',
  `cocinero_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `detalle_pedido`
--

INSERT INTO `detalle_pedido` (`id`, `pedido_id`, `plato_id`, `cantidad`, `descripcion`, `estado`, `cocinero_id`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 2, 'Sin albahaca', 'pendiente', NULL, '2025-08-22 16:57:01', '2025-08-22 16:57:01'),
(2, 2, 2, 1, 'Extra aderezo', 'cocinando', 4, '2025-08-22 16:57:01', '2025-08-22 16:57:01'),
(3, 3, 3, 3, NULL, 'listo', 4, '2025-08-22 16:57:01', '2025-08-22 16:57:01');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `gastos`
--

CREATE TABLE `gastos` (
  `id` int(11) NOT NULL,
  `cajero_id` int(11) NOT NULL,
  `descripcion` text NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp(),
  `sucursal_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `gastos`
--

INSERT INTO `gastos` (`id`, `cajero_id`, `descripcion`, `monto`, `foto`, `fecha`, `sucursal_id`) VALUES
(1, 4, 'Compra de ingredientes', 50.00, NULL, '2025-08-22 16:57:01', 1),
(2, 4, 'Reparación de equipo', 30.00, 'ruta/foto1.jpg', '2025-08-22 16:57:01', 1),
(3, 4, 'Limpieza', 15.00, NULL, '2025-08-22 16:57:01', 2);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `menu`
--

CREATE TABLE `menu` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `precio` decimal(10,2) NOT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `categoria` varchar(50) DEFAULT NULL,
  `estado` enum('disponible','no_disponible') DEFAULT 'disponible',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `menu`
--

INSERT INTO `menu` (`id`, `nombre`, `descripcion`, `precio`, `foto`, `categoria`, `estado`, `created_at`, `updated_at`) VALUES
(1, 'Pizza Margherita', 'Pizza con tomate, mozzarella y albahaca', 12.50, '68ae1b8f652af.png', 'plato_fuerte', 'disponible', '2025-08-22 16:57:01', '2025-08-26 20:39:43'),
(2, 'Ensalada César', 'Ensalada con lechuga, pollo, croutones y aderezo César', 8.75, NULL, 'entrada', 'disponible', '2025-08-22 16:57:01', '2025-08-22 16:57:01'),
(3, 'Tiramisú', 'Postre de café con mascarpone', 6.00, NULL, 'postre', 'disponible', '2025-08-22 16:57:01', '2025-08-26 20:40:04'),
(4, 'limonada', 'limonada natural', 7.00, '68ae1d11d8639.png', 'bebida', 'disponible', '2025-08-26 20:46:09', '2025-08-26 20:46:09');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mesas`
--

CREATE TABLE `mesas` (
  `id` int(11) NOT NULL,
  `numero` int(11) NOT NULL,
  `sucursal_id` int(11) NOT NULL,
  `estado` enum('libre','ocupada','cerrada','en_preparacion') DEFAULT 'libre',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `mesas`
--

INSERT INTO `mesas` (`id`, `numero`, `sucursal_id`, `estado`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'libre', '2025-08-22 16:57:01', '2025-08-22 16:57:01'),
(2, 2, 1, 'ocupada', '2025-08-22 16:57:01', '2025-08-22 16:57:01'),
(3, 3, 2, 'cerrada', '2025-08-22 16:57:01', '2025-08-26 21:43:07'),
(4, 4, 1, 'libre', '2025-08-26 21:43:24', '2025-08-26 21:44:08');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pedidos`
--

CREATE TABLE `pedidos` (
  `id` int(11) NOT NULL,
  `mesa_id` int(11) NOT NULL,
  `mesero_id` int(11) NOT NULL,
  `sucursal_id` int(11) NOT NULL,
  `estado` enum('pendiente','en_proceso','listo','cerrado') DEFAULT 'pendiente',
  `hora_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `pedidos`
--

INSERT INTO `pedidos` (`id`, `mesa_id`, `mesero_id`, `sucursal_id`, `estado`, `hora_creacion`, `updated_at`) VALUES
(1, 1, 3, 1, 'pendiente', '2025-08-22 16:57:01', '2025-08-22 16:57:01'),
(2, 2, 3, 1, 'en_proceso', '2025-08-22 16:57:01', '2025-08-22 16:57:01'),
(3, 3, 3, 2, 'listo', '2025-08-22 16:57:01', '2025-08-22 16:57:01');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `nombre` enum('admin','mesero','cajero','cocinero') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `roles`
--

INSERT INTO `roles` (`id`, `nombre`) VALUES
(1, 'admin'),
(2, 'mesero'),
(3, 'cajero'),
(4, 'cocinero');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sucursales`
--

CREATE TABLE `sucursales` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `estado` enum('activa','inactiva') DEFAULT 'activa',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `sucursales`
--

INSERT INTO `sucursales` (`id`, `nombre`, `direccion`, `estado`, `created_at`, `updated_at`) VALUES
(1, 'Sucursal Centro', 'Calle Principal 123, Manizales', 'activa', '2025-08-22 15:30:33', '2025-08-22 15:30:33'),
(2, 'Sucursal Centro', 'Calle Principal 123, Ciudad', 'activa', '2025-08-22 16:56:45', '2025-08-22 16:56:45'),
(3, 'Sucursal Norte', 'Avenida Norte 456, Ciudad', 'activa', '2025-08-22 16:56:45', '2025-08-22 16:56:45'),
(4, 'Sucursal Sur', 'Calle Sur 789, Ciudad', 'activa', '2025-08-22 16:56:45', '2025-08-22 16:56:45'),
(5, 'Sucursal Centro', 'Calle Principal 123, Ciudad', 'activa', '2025-08-22 16:57:00', '2025-08-22 16:57:00'),
(6, 'Sucursal Norte', 'Avenida Norte 456, Ciudad', 'activa', '2025-08-22 16:57:00', '2025-08-22 16:57:00'),
(7, 'Sucursal Sur', 'Calle Sur 789, Ciudad', 'activa', '2025-08-22 16:57:00', '2025-08-22 16:57:00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `contraseña` varchar(255) NOT NULL,
  `documento` int(11) NOT NULL,
  `rol_id` int(11) NOT NULL,
  `sucursal_id` int(11) DEFAULT NULL,
  `estado` enum('activo','inactivo') DEFAULT 'activo',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre`, `contraseña`, `documento`, `rol_id`, `sucursal_id`, `estado`, `created_at`, `updated_at`) VALUES
(1, 'admin', '12345678', 1054398407, 1, 1, 'activo', '2025-08-22 15:30:34', '2025-08-26 22:45:20'),
(2, 'admin', '12345678', 1234123123, 1, 1, 'activo', '2025-08-22 16:57:01', '2025-08-27 19:07:18'),
(3, 'mesero1', 'pass1234', 1234123123, 2, 1, 'inactivo', '2025-08-22 16:57:01', '2025-08-27 19:07:32'),
(4, 'cajero1', 'pass5678', 1234123123, 3, 2, 'activo', '2025-08-22 16:57:01', '2025-08-27 19:07:36'),
(5, 'Mateo', '12345678', 1234123123, 2, 1, 'activo', '2025-08-26 18:52:30', '2025-08-27 19:07:41');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ventas`
--

CREATE TABLE `ventas` (
  `id` int(11) NOT NULL,
  `pedido_id` int(11) NOT NULL,
  `cajero_id` int(11) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `tipo_pago` enum('efectivo','transferencia') NOT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp(),
  `sucursal_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `ventas`
--

INSERT INTO `ventas` (`id`, `pedido_id`, `cajero_id`, `total`, `tipo_pago`, `fecha`, `sucursal_id`) VALUES
(1, 1, 4, 25.00, 'efectivo', '2025-08-22 16:57:01', 1),
(2, 2, 4, 8.75, 'efectivo', '2025-08-22 16:57:01', 1),
(3, 3, 4, 18.00, 'efectivo', '2025-08-22 16:57:01', 2);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `caja`
--
ALTER TABLE `caja`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cajero_id` (`cajero_id`),
  ADD KEY `sucursal_id` (`sucursal_id`);

--
-- Indices de la tabla `detalle_pedido`
--
ALTER TABLE `detalle_pedido`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pedido_id` (`pedido_id`),
  ADD KEY `plato_id` (`plato_id`),
  ADD KEY `cocinero_id` (`cocinero_id`);

--
-- Indices de la tabla `gastos`
--
ALTER TABLE `gastos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cajero_id` (`cajero_id`),
  ADD KEY `sucursal_id` (`sucursal_id`);

--
-- Indices de la tabla `menu`
--
ALTER TABLE `menu`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `mesas`
--
ALTER TABLE `mesas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sucursal_id` (`sucursal_id`);

--
-- Indices de la tabla `pedidos`
--
ALTER TABLE `pedidos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `mesa_id` (`mesa_id`),
  ADD KEY `mesero_id` (`mesero_id`),
  ADD KEY `sucursal_id` (`sucursal_id`);

--
-- Indices de la tabla `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `sucursales`
--
ALTER TABLE `sucursales`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD KEY `rol_id` (`rol_id`),
  ADD KEY `sucursal_id` (`sucursal_id`);

--
-- Indices de la tabla `ventas`
--
ALTER TABLE `ventas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pedido_id` (`pedido_id`),
  ADD KEY `cajero_id` (`cajero_id`),
  ADD KEY `sucursal_id` (`sucursal_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `caja`
--
ALTER TABLE `caja`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `detalle_pedido`
--
ALTER TABLE `detalle_pedido`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `gastos`
--
ALTER TABLE `gastos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `menu`
--
ALTER TABLE `menu`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `mesas`
--
ALTER TABLE `mesas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `pedidos`
--
ALTER TABLE `pedidos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `sucursales`
--
ALTER TABLE `sucursales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `ventas`
--
ALTER TABLE `ventas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `caja`
--
ALTER TABLE `caja`
  ADD CONSTRAINT `caja_ibfk_1` FOREIGN KEY (`cajero_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `caja_ibfk_2` FOREIGN KEY (`sucursal_id`) REFERENCES `sucursales` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `detalle_pedido`
--
ALTER TABLE `detalle_pedido`
  ADD CONSTRAINT `detalle_pedido_ibfk_1` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `detalle_pedido_ibfk_2` FOREIGN KEY (`plato_id`) REFERENCES `menu` (`id`),
  ADD CONSTRAINT `detalle_pedido_ibfk_3` FOREIGN KEY (`cocinero_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `gastos`
--
ALTER TABLE `gastos`
  ADD CONSTRAINT `gastos_ibfk_1` FOREIGN KEY (`cajero_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `gastos_ibfk_2` FOREIGN KEY (`sucursal_id`) REFERENCES `sucursales` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `mesas`
--
ALTER TABLE `mesas`
  ADD CONSTRAINT `mesas_ibfk_1` FOREIGN KEY (`sucursal_id`) REFERENCES `sucursales` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `pedidos`
--
ALTER TABLE `pedidos`
  ADD CONSTRAINT `pedidos_ibfk_1` FOREIGN KEY (`mesa_id`) REFERENCES `mesas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pedidos_ibfk_2` FOREIGN KEY (`mesero_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `pedidos_ibfk_3` FOREIGN KEY (`sucursal_id`) REFERENCES `sucursales` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`rol_id`) REFERENCES `roles` (`id`),
  ADD CONSTRAINT `usuarios_ibfk_2` FOREIGN KEY (`sucursal_id`) REFERENCES `sucursales` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `ventas`
--
ALTER TABLE `ventas`
  ADD CONSTRAINT `ventas_ibfk_1` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`),
  ADD CONSTRAINT `ventas_ibfk_2` FOREIGN KEY (`cajero_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `ventas_ibfk_3` FOREIGN KEY (`sucursal_id`) REFERENCES `sucursales` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
