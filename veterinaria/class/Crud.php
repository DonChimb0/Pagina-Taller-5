<?php
class Crud {
    private $conexion;

    public function __construct($conexion) {
        $this->conexion = $conexion;
    }

    // Función para insertar datos en una tabla
    public function insertar($tabla, $datos) {
        // Preparar los datos para la inserción
        $columnas = implode(",", array_keys($datos));
        $valores = "'" . implode("','", array_values($datos)) . "'";

        // Consulta SQL
        $query = "INSERT INTO $tabla ($columnas) VALUES ($valores)";
        
        if ($this->conexion->query($query) === TRUE) {
            return true;
        } else {
            return false;
        }
    }

    // Función para actualizar datos en una tabla
    public function actualizar($tabla, $datos, $id) {
        $set = [];
        foreach ($datos as $columna => $valor) {
            $set[] = "$columna = '$valor'";
        }
        $setQuery = implode(",", $set);

        // Consulta SQL
        $query = "UPDATE $tabla SET $setQuery WHERE id = $id";

        if ($this->conexion->query($query) === TRUE) {
            return true;
        } else {
            return false;
        }
    }

    // Función para eliminar datos de una tabla
    public function eliminar($tabla, $id) {
        // Consulta SQL
        $query = "DELETE FROM $tabla WHERE id = $id";
        
        if ($this->conexion->query($query) === TRUE) {
            return true;
        } else {
            return false;
        }
    }

    // Función para obtener todos los datos de una tabla
    public function obtenerTodos($tabla) {
        $query = "SELECT * FROM $tabla";
        $resultado = $this->conexion->query($query);
        return $resultado;
    }

    // Función para obtener un registro por su ID
    public function obtenerPorId($tabla, $id) {
        $query = "SELECT * FROM $tabla WHERE id = $id";
        $resultado = $this->conexion->query($query);
        return $resultado->fetch_assoc();
    }

    // Función para ejecutar la creación de triggers
    public function crearTriggers() {
        // Trigger para alertar sobre próximas vacunaciones
        $query = "
        DELIMITER $$

        CREATE TRIGGER alerta_vacunacion_proxima
        AFTER INSERT ON citas
        FOR EACH ROW
        BEGIN
            DECLARE vacuna_date DATE;
            DECLARE mascota_nombre VARCHAR(255);
            
            -- Obtener la fecha de vacunación de la mascota relacionada
            SELECT fecha_vacunacion, nombre INTO vacuna_date, mascota_nombre
            FROM mascotas
            WHERE id = NEW.mascota_id;
            
            -- Si la fecha de vacunación está a menos de 7 días
            IF DATEDIFF(vacuna_date, CURDATE()) <= 7 THEN
                SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = CONCAT('Alerta: La mascota ', mascota_nombre, ' tiene próxima su vacunación en ', DATEDIFF(vacuna_date, CURDATE()), ' días.');
            END IF;
        END $$

        DELIMITER ;
        ";

        $this->conexion->query($query);

        // Trigger para registrar cambios en el historial médico
        $query = "
        DELIMITER $$

        CREATE TRIGGER registrar_historial_medico
        AFTER UPDATE ON mascotas
        FOR EACH ROW
        BEGIN
            IF OLD.historial_medico != NEW.historial_medico THEN
                INSERT INTO historial_medico (mascota_id, fecha_cambio, descripcion)
                VALUES (NEW.id, CURDATE(), CONCAT('Cambio en historial médico: ', OLD.historial_medico, ' -> ', NEW.historial_medico));
            END IF;
        END $$

        DELIMITER ;
        ";

        $this->conexion->query($query);

        // Trigger para control de stock de medicamentos
        $query = "
        DELIMITER $$

        CREATE TRIGGER control_stock_medicamentos
        AFTER UPDATE ON inventario
        FOR EACH ROW
        BEGIN
            IF NEW.cantidad < 0 THEN
                SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = CONCAT('Error: No hay suficiente stock para el medicamento ', NEW.medicamento, '. Cantidad negativa no permitida.');
            END IF;
        END $$

        DELIMITER ;
        ";

        $this->conexion->query($query);
    }
}
?>
