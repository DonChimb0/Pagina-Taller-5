-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 11-12-2024 a las 02:53:09
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `veterinaria`
--

DELIMITER $$
--
-- Procedimientos
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `procesar_recordatorios_citas_proximas` ()   BEGIN
    -- Declaraciones de variables
    DECLARE mensaje VARCHAR(255);
    DECLARE done INT DEFAULT 0;
    DECLARE cita_id INT;
    DECLARE mascota_nombre VARCHAR(255);
    DECLARE cita_fecha DATE;
    
    -- Declaración del cursor
    DECLARE citas_cursor CURSOR FOR
        SELECT c.id, m.nombre, c.fecha
        FROM citas c
        JOIN mascotas m ON c.id_mascota = m.id
        WHERE DATEDIFF(c.fecha, CURDATE()) BETWEEN 1 AND 7;
    
    -- Declaración de un manejador para cuando se termina el cursor
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;
    
    -- Abrir el cursor
    OPEN citas_cursor;
    
    -- Bucle para procesar cada fila
    read_loop: LOOP
        FETCH citas_cursor INTO cita_id, mascota_nombre, cita_fecha;
        
        -- Terminar si ya no hay más filas
        IF done THEN
            LEAVE read_loop;
        END IF;
        
        -- Construir el mensaje de recordatorio
        SET mensaje = CONCAT('Recordatorio: La cita de ', mascota_nombre, ' está programada para el ', cita_fecha, '.');
        
        -- Insertar el mensaje de recordatorio en la tabla de alertas
        INSERT INTO alertas_citas (mensaje, fecha_alerta) VALUES (mensaje, CURDATE());
    END LOOP;
    
    -- Cerrar el cursor
    CLOSE citas_cursor;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `programar_cita` (IN `p_id_mascota` INT, IN `p_fecha` DATE, IN `p_tipo` VARCHAR(50), IN `p_detalle` TEXT)   BEGIN
    INSERT INTO citas (id_mascota, fecha, tipo, detalle)
    VALUES (p_id_mascota, p_fecha, p_tipo, p_detalle);
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `registrar_tratamiento` (IN `p_id_mascota` INT, IN `p_fecha_inicio` DATE, IN `p_fecha_fin` DATE, IN `p_descripcion` TEXT)   BEGIN
    INSERT INTO tratamientos (id_mascota, fecha_inicio, fecha_fin, descripcion)
    VALUES (p_id_mascota, p_fecha_inicio, p_fecha_fin, p_descripcion);
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `registro_nueva_mascota` (IN `p_nombre` VARCHAR(100), IN `p_especie` VARCHAR(50), IN `p_raza` VARCHAR(50), IN `p_fecha_nacimiento` DATE)   BEGIN
    INSERT INTO mascotas (nombre, especie, raza, fecha_nacimiento)
    VALUES (p_nombre, p_especie, p_raza, p_fecha_nacimiento);
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `citas`
--

CREATE TABLE `citas` (
  `id` int(11) NOT NULL,
  `mascota_id` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `descripcion` text DEFAULT NULL,
  `tipo` varchar(255) DEFAULT NULL,
  `cita_tipo` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Disparadores `citas`
--
DELIMITER $$
CREATE TRIGGER `alertar_proximas_vacunaciones` AFTER UPDATE ON `citas` FOR EACH ROW BEGIN
    DECLARE mensaje VARCHAR(255);
    
    -- Verificar si la cita es para una vacunación
    IF NEW.tipo = 'vacunacion' AND DATEDIFF(NEW.fecha, CURDATE()) <= 7 AND DATEDIFF(NEW.fecha, CURDATE()) >= 0 THEN
        -- Obtener el nombre de la mascota usando el id_mascota de la tabla citas
        SET mensaje = CONCAT('¡Recordatorio! La vacunación de ', (SELECT nombre FROM mascotas WHERE id = NEW.mascota_id), ' está programada para dentro de ', DATEDIFF(NEW.fecha, CURDATE()), ' días.');
        
        -- Aquí puedes almacenar el mensaje en una tabla de alertas o enviarlo
        -- Ejemplo de insertar en una tabla de alertas
        INSERT INTO alertas_vacunacion (mensaje, fecha_alerta) VALUES (mensaje, CURDATE());
    END IF;
    
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `dueños`
--

CREATE TABLE `dueños` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `telefono` varchar(15) DEFAULT NULL,
  `direccion` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `historial_medico`
--

CREATE TABLE `historial_medico` (
  `id` int(11) NOT NULL,
  `id_mascota` int(11) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `fecha` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Disparadores `historial_medico`
--
DELIMITER $$
CREATE TRIGGER `registrar_cambio_historial_medico` AFTER UPDATE ON `historial_medico` FOR EACH ROW BEGIN
    DECLARE mensaje TEXT;
    
    -- Registra un mensaje de cambio en el historial médico
    SET mensaje = CONCAT('Cambio registrado en el historial médico de ', (SELECT nombre FROM mascotas WHERE id = NEW.id_mascota), ' el ', CURDATE(), '. Descripción previa: ', OLD.descripcion, ', Descripción nueva: ', NEW.descripcion);
    
    -- Insertar en la tabla de auditoría
    INSERT INTO cambios_historial (mensaje) VALUES (mensaje);
    
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inventario`
--

CREATE TABLE `inventario` (
  `id` int(11) NOT NULL,
  `nombre_medicamento` varchar(255) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `precio` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Disparadores `inventario`
--
DELIMITER $$
CREATE TRIGGER `control_stock_medicamentos` AFTER UPDATE ON `inventario` FOR EACH ROW BEGIN
    DECLARE mensaje_error VARCHAR(255);
    
    -- Verificamos si la cantidad es menor que cero
    IF NEW.cantidad < 0 THEN
        SET mensaje_error = CONCAT('Error: No hay suficiente stock para el medicamento ', NEW.nombre_medicamento, '. Cantidad negativa no permitida.');
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = mensaje_error;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mascotas`
--

CREATE TABLE `mascotas` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `especie` varchar(50) NOT NULL,
  `raza` varchar(50) NOT NULL,
  `edad` int(11) NOT NULL,
  `dueño_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `citas`
--
ALTER TABLE `citas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `mascota_id` (`mascota_id`);

--
-- Indices de la tabla `dueños`
--
ALTER TABLE `dueños`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `historial_medico`
--
ALTER TABLE `historial_medico`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_mascota` (`id_mascota`);

--
-- Indices de la tabla `inventario`
--
ALTER TABLE `inventario`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `mascotas`
--
ALTER TABLE `mascotas`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `citas`
--
ALTER TABLE `citas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `dueños`
--
ALTER TABLE `dueños`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `historial_medico`
--
ALTER TABLE `historial_medico`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `inventario`
--
ALTER TABLE `inventario`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `mascotas`
--
ALTER TABLE `mascotas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `citas`
--
ALTER TABLE `citas`
  ADD CONSTRAINT `citas_ibfk_1` FOREIGN KEY (`mascota_id`) REFERENCES `mascotas` (`id`);

--
-- Filtros para la tabla `historial_medico`
--
ALTER TABLE `historial_medico`
  ADD CONSTRAINT `historial_medico_ibfk_1` FOREIGN KEY (`id_mascota`) REFERENCES `mascotas` (`id`);

DELIMITER $$
--
-- Eventos
--
CREATE DEFINER=`root`@`localhost` EVENT `actualizar_calendarios_vacunacion` ON SCHEDULE EVERY 1 MONTH STARTS '2024-12-10 20:50:39' ON COMPLETION NOT PRESERVE ENABLE DO BEGIN
    DECLARE mensaje VARCHAR(255);

    -- Actualiza las fechas de vacunación cada mes
    UPDATE citas
    SET fecha = DATE_ADD(fecha, INTERVAL 1 YEAR)
    WHERE tipo = 'vacunacion' AND fecha <= CURDATE();

    -- Registrar la actualización en una tabla de registros (puede ser opcional)
    SET mensaje = 'Calendarios de vacunación actualizados.';
    INSERT INTO registros_actualizacion (mensaje, fecha_registro) VALUES (mensaje, CURDATE());
END$$

CREATE DEFINER=`root`@`localhost` EVENT `reporte_tratamientos_mes` ON SCHEDULE EVERY 1 MONTH STARTS '2024-12-10 20:51:33' ON COMPLETION NOT PRESERVE ENABLE DO BEGIN
    DECLARE mensaje TEXT;
    DECLARE done INT DEFAULT 0;
    
    DECLARE tratamiento_id INT;
    DECLARE mascota_nombre VARCHAR(255);
    DECLARE fecha_inicio DATE;
    DECLARE fecha_fin DATE;
    DECLARE descripcion TEXT;

    -- Declaración del cursor para los tratamientos del mes anterior
    DECLARE tratamientos_cursor CURSOR FOR
    SELECT t.id, m.nombre, t.fecha_inicio, t.fecha_fin, t.descripcion
    FROM tratamientos t
    JOIN mascotas m ON t.id_mascota = m.id
    WHERE MONTH(t.fecha_inicio) = MONTH(CURDATE()) - 1
    AND YEAR(t.fecha_inicio) = YEAR(CURDATE());

    -- Declaración del manejador para manejar el fin del cursor
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;

    -- Abrir el cursor
    OPEN tratamientos_cursor;

    -- Bucle para procesar cada fila
    read_loop: LOOP
        FETCH tratamientos_cursor INTO tratamiento_id, mascota_nombre, fecha_inicio, fecha_fin, descripcion;

        -- Terminar si ya no hay más filas
        IF done THEN
            LEAVE read_loop;
        END IF;

        -- Generar el mensaje del reporte
        SET mensaje = CONCAT('Tratamiento realizado a ', mascota_nombre, ' desde ', fecha_inicio, ' hasta ', fecha_fin, '. Descripción: ', descripcion);

        -- Insertar el mensaje en la tabla de reportes
        INSERT INTO reportes_tratamientos (mensaje, fecha_reporte) VALUES (mensaje, CURDATE());
    END LOOP;

    -- Cerrar el cursor
    CLOSE tratamientos_cursor;
END$$

DELIMITER ;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
