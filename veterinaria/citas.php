<?php
// Conexión a la base de datos
$conexion = new mysqli("localhost", "root", "", "veterinaria");
if ($conexion->connect_error) {
    die("Conexión fallida: " . $conexion->connect_error);
}

// Incluir el archivo Crud.php
include_once 'class/Crud.php';

// Crear una instancia de la clase Crud
$crud = new Crud($conexion);

// Verificar si se ha enviado un formulario para insertar, actualizar o eliminar
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Agregar una nueva cita
    if (isset($_POST['agregar'])) {
        $nombre_mascota = htmlspecialchars($_POST['mascota_nombre']);
        $fecha = htmlspecialchars($_POST['fecha']);
        $descripcion = htmlspecialchars($_POST['descripcion']);

        // Obtener el ID de la mascota a partir del nombre
        $stmt = $conexion->prepare("SELECT id FROM mascotas WHERE nombre = ? LIMIT 1");
        $stmt->bind_param("s", $nombre_mascota);  // 's' indica que es una cadena
        $stmt->execute();
        $resultado = $stmt->get_result();

        if ($resultado->num_rows > 0) {
            $mascota = $resultado->fetch_assoc();
            $mascota_id = $mascota['id'];

            // Insertar la nueva cita
            $query = "INSERT INTO citas (mascota_id, fecha, descripcion) VALUES ('$mascota_id', '$fecha', '$descripcion')";
            if ($conexion->query($query) === TRUE) {
                echo "<script>Swal.fire('¡Éxito!', 'Cita agregada correctamente', 'success');</script>";
            } else {
                echo "<script>Swal.fire('¡Error!', 'Hubo un error al agregar la cita: " . $conexion->error . "', 'error');</script>";
            }
        } else {
            echo "<script>Swal.fire('¡Error!', 'No se encontró la mascota', 'error');</script>";
        }
    }
    
    // Actualizar una cita existente
    if (isset($_POST['actualizar'])) {
        $id_cita = $_POST['id'];
        $fecha = htmlspecialchars($_POST['fecha']);
        $descripcion = htmlspecialchars($_POST['descripcion']);

        // Actualizar la cita
        $query = "UPDATE citas SET fecha = '$fecha', descripcion = '$descripcion' WHERE id = '$id_cita'";
        if ($conexion->query($query) === TRUE) {
            echo "<script>Swal.fire('¡Éxito!', 'Cita actualizada correctamente', 'success');</script>";
        } else {
            echo "<script>Swal.fire('¡Error!', 'Hubo un error al actualizar la cita', 'error');</script>";
        }
    }

    // Eliminar una cita
    if (isset($_POST['eliminar'])) {
        $id_cita = $_POST['id'];
        // Eliminar la cita
        $query = "DELETE FROM citas WHERE id = '$id_cita'";
        if ($conexion->query($query) === TRUE) {
            echo "<script>Swal.fire('¡Éxito!', 'Cita eliminada correctamente', 'success');</script>";
        } else {
            echo "<script>Swal.fire('¡Error!', 'Hubo un error al eliminar la cita', 'error');</script>";
        }
    }
}

// Obtener la lista de citas con el nombre de la mascota (usando JOIN)
$query = "SELECT citas.id, citas.fecha, citas.descripcion, mascotas.nombre AS mascota_nombre
          FROM citas
          JOIN mascotas ON citas.mascota_id = mascotas.id";
$citas = $conexion->query($query);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Veterinaria - Gestión de Citas</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <header class="bg-primary text-white p-4">
        <div class="container">
            <h1>Sistema de Gestión Veterinaria</h1>
            <nav class="nav">
                <a class="nav-link text-white" href="mascotas.php">Gestión de Mascotas</a>
                <a class="nav-link text-white" href="dueños.php">Gestión de Dueños</a>
                <a class="nav-link text-white" href="citas.php">Citas Médicas</a>
                <a class="nav-link text-white" href="inventario.php">Inventario</a>
            </nav>
        </div>
    </header>

    <main class="container mt-4">
        <section class="mb-4">
            <h2>Lista de Citas</h2>
            <div class="list-group">
                <?php if ($citas->num_rows > 0): ?>
                    <?php while ($cita = $citas->fetch_assoc()): ?>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <strong>Nombre de la Mascota:</strong> <?php echo $cita['mascota_nombre']; ?> - 
                            <strong>Fecha:</strong> <?php echo $cita['fecha']; ?> - 
                            <strong>Descripción:</strong> <?php echo $cita['descripcion']; ?>
                            <div>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="id" value="<?php echo $cita['id']; ?>">
                                    <button type="submit" name="eliminar" class="btn btn-danger btn-sm m-1">Eliminar</button>
                                </form>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="id" value="<?php echo $cita['id']; ?>">
                                    <input type="date" class="form-control-sm" name="fecha" value="<?php echo $cita['fecha']; ?>" required>
                                    <textarea class="form-control-sm" name="descripcion" required><?php echo $cita['descripcion']; ?></textarea>
                                    <button type="submit" name="actualizar" class="btn btn-warning btn-sm m-1">Actualizar</button>
                                </form>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p>No se encontraron citas.</p>
                <?php endif; ?>
            </div>
        </section>

        <section>
            <h3>Agregar Nueva Cita</h3>
            <form method="POST">
                <div class="mb-3">
                    <label for="fecha" class="form-label">Fecha:</label>
                    <input type="date" class="form-control" id="fecha" name="fecha" required>
                </div>
                <div class="mb-3">
                    <label for="mascota_nombre" class="form-label">Nombre de la Mascota:</label>
                    <input type="text" class="form-control" id="mascota_nombre" name="mascota_nombre" required>
                </div>
                <div class="mb-3">
                    <label for="descripcion" class="form-label">Descripción de la Cita:</label>
                    <textarea class="form-control" id="descripcion" name="descripcion" rows="3" required></textarea>
                </div>
                <button type="submit" class="btn btn-primary" name="agregar">Agregar Cita</button>
            </form>
        </section>
    </main>

    <footer class="bg-dark text-white text-center py-3">
        <p>&copy; 2024 Veterinaria</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.1/dist/sweetalert2.all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
