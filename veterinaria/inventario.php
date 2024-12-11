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
    // Agregar un nuevo producto al inventario
    if (isset($_POST['agregar'])) {
        $datos = [
            'nombre' => $_POST['nombre'],
            'cantidad' => $_POST['cantidad'],
            'precio' => $_POST['precio']
        ];
        $crud->insertar('inventario', $datos);
    }

    // Actualizar un producto en el inventario
    if (isset($_POST['actualizar'])) {
        $datos = [
            'nombre' => $_POST['nombre'],
            'cantidad' => $_POST['cantidad'],
            'precio' => $_POST['precio']
        ];
        $crud->actualizar('inventario', $datos, $_POST['id']);
    }

    // Eliminar un producto del inventario
    if (isset($_POST['eliminar'])) {
        $crud->eliminar('inventario', $_POST['id']);
    }
}

// Obtener la lista de productos en el inventario
$productos = $crud->obtenerTodos('inventario');
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Veterinaria - Gestión de Inventario</title>
    <!-- Agregar Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Agregar SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.1/dist/sweetalert2.min.css">
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
        <h2>Gestión de Inventario</h2>
        
        <!-- Lista de Productos -->
        <section>
            <h3>Lista de Productos</h3>
            <ul class="list-group">
                <?php if ($productos->num_rows > 0): ?>
                    <?php while ($producto = $productos->fetch_assoc()): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Producto: <?php echo $producto['nombre']; ?> - Cantidad: <?php echo $producto['cantidad']; ?> - Precio: $<?php echo $producto['precio']; ?>
                            <div>
                                <!-- Eliminar Producto -->
                                <button class="btn btn-danger btn-sm delete-btn" data-id="<?php echo $producto['id']; ?>">Eliminar</button>

                                <!-- Editar Producto -->
                                <button class="btn btn-warning btn-sm edit-btn" data-id="<?php echo $producto['id']; ?>" data-nombre="<?php echo $producto['nombre']; ?>" data-cantidad="<?php echo $producto['cantidad']; ?>" data-precio="<?php echo $producto['precio']; ?>">Editar</button>
                            </div>
                        </li>
                    <?php endwhile; ?>
                <?php else: ?>
                    <li class="list-group-item">No se encontraron productos.</li>
                <?php endif; ?>
            </ul>
        </section>

        <!-- Formulario para agregar nuevo producto -->
        <section class="mt-4">
            <h3>Agregar Nuevo Producto</h3>
            <form method="POST">
                <div class="mb-3">
                    <label for="nombre" class="form-label">Producto:</label>
                    <input type="text" class="form-control" id="nombre" name="nombre" required>
                </div>
                <div class="mb-3">
                    <label for="cantidad" class="form-label">Cantidad:</label>
                    <input type="number" class="form-control" id="cantidad" name="cantidad" required>
                </div>
                <div class="mb-3">
                    <label for="precio" class="form-label">Precio:</label>
                    <input type="number" class="form-control" id="precio" name="precio" required>
                </div>
                <button type="submit" class="btn btn-primary" name="agregar">Agregar Producto</button>
            </form>
        </section>
    </main>

    <!-- Footer -->
    <footer class="bg-dark text-white text-center py-3">
        <p>&copy; 2024 Veterinaria</p>
    </footer>

    <!-- Agregar scripts -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.1/dist/sweetalert2.all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Función para eliminar producto con SweetAlert2
        document.querySelectorAll('.delete-btn').forEach(button => {
            button.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                Swal.fire({
                    title: '¿Estás seguro?',
                    text: "¡No podrás revertir esta acción!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Sí, eliminar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Enviar el formulario de eliminación
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.innerHTML = `<input type="hidden" name="id" value="${id}">
                                          <input type="hidden" name="eliminar" value="true">`;
                        document.body.appendChild(form);
                        form.submit();
                    }
                });
            });
        });

        // Función para editar producto con SweetAlert2
        document.querySelectorAll('.edit-btn').forEach(button => {
            button.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const nombre = this.getAttribute('data-nombre');
                const cantidad = this.getAttribute('data-cantidad');
                const precio = this.getAttribute('data-precio');

                Swal.fire({
                    title: 'Editar Producto',
                    html: `
                        <input type="text" id="edit-nombre" class="swal2-input" value="${nombre}" required>
                        <input type="number" id="edit-cantidad" class="swal2-input" value="${cantidad}" required>
                        <input type="number" id="edit-precio" class="swal2-input" value="${precio}" required>
                    `,
                    preConfirm: () => {
                        const newNombre = document.getElementById('edit-nombre').value;
                        const newCantidad = document.getElementById('edit-cantidad').value;
                        const newPrecio = document.getElementById('edit-precio').value;

                        // Enviar el formulario de actualización
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.innerHTML = `<input type="hidden" name="id" value="${id}">
                                          <input type="hidden" name="actualizar" value="true">
                                          <input type="hidden" name="nombre" value="${newNombre}">
                                          <input type="hidden" name="cantidad" value="${newCantidad}">
                                          <input type="hidden" name="precio" value="${newPrecio}">`;
                        document.body.appendChild(form);
                        form.submit();
                    },
                    showCancelButton: true,
                    confirmButtonText: 'Actualizar',
                    cancelButtonText: 'Cancelar',
                });
            });
        });
    </script>
</body>
</html>
