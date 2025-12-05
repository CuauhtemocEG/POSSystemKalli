<?php
require_once 'conexion.php';

$pdo = conexion();

// Obtener usuarios con sus roles
$usuarios = $pdo->query("
    SELECT u.*, r.nombre as rol_nombre 
    FROM usuarios u 
    JOIN roles r ON u.rol_id = r.id 
    ORDER BY u.creado_en DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Obtener roles para el formulario
$roles = $pdo->query("SELECT * FROM roles ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="max-w-6xl mx-auto py-6 px-4">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-3xl font-bold text-gray-800 dark:text-white">
            <i class="bi bi-people mr-2"></i>Gestión de Usuarios
        </h2>
        <button onclick="showCreateUserModal()" 
                class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg">
            <i class="bi bi-person-plus mr-2"></i>Nuevo Usuario
        </button>
    </div>

    <!-- Estadísticas -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-blue-100 p-4 rounded-lg">
            <div class="flex items-center">
                <i class="bi bi-people text-blue-600 text-2xl mr-3"></i>
                <div>
                    <p class="text-sm text-blue-600">Total Usuarios</p>
                    <p class="text-xl font-bold text-blue-800"><?= count($usuarios) ?></p>
                </div>
            </div>
        </div>
        <div class="bg-green-100 p-4 rounded-lg">
            <div class="flex items-center">
                <i class="bi bi-check-circle text-green-600 text-2xl mr-3"></i>
                <div>
                    <p class="text-sm text-green-600">Activos</p>
                    <p class="text-xl font-bold text-green-800">
                        <?= count(array_filter($usuarios, fn($u) => $u['activo'])) ?>
                    </p>
                </div>
            </div>
        </div>
        <div class="bg-yellow-100 p-4 rounded-lg">
            <div class="flex items-center">
                <i class="bi bi-person-badge text-yellow-600 text-2xl mr-3"></i>
                <div>
                    <p class="text-sm text-yellow-600">Meseros</p>
                    <p class="text-xl font-bold text-yellow-800">
                        <?= count(array_filter($usuarios, fn($u) => $u['rol_nombre'] === 'mesero')) ?>
                    </p>
                </div>
            </div>
        </div>
        <div class="bg-purple-100 p-4 rounded-lg">
            <div class="flex items-center">
                <i class="bi bi-shield-check text-purple-600 text-2xl mr-3"></i>
                <div>
                    <p class="text-sm text-purple-600">Administradores</p>
                    <p class="text-xl font-bold text-purple-800">
                        <?= count(array_filter($usuarios, fn($u) => $u['rol_nombre'] === 'administrador')) ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de usuarios -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Usuario
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Email
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Rol
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Estado
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Último Login
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Acciones
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($usuarios as $usuario): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-gray-200 rounded-full flex items-center justify-center">
                                    <i class="bi bi-person text-gray-600"></i>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?= htmlspecialchars($usuario['nombre_completo']) ?>
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        @<?= htmlspecialchars($usuario['username']) ?>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900"><?= htmlspecialchars($usuario['email']) ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                <?php
                                switch($usuario['rol_nombre']) {
                                    case 'administrador': echo 'bg-purple-100 text-purple-800'; break;
                                    case 'mesero': echo 'bg-blue-100 text-blue-800'; break;
                                    case 'cocinero': echo 'bg-orange-100 text-orange-800'; break;
                                    case 'bartender': echo 'bg-cyan-100 text-cyan-800'; break;
                                    case 'cajero': echo 'bg-green-100 text-green-800'; break;
                                    default: echo 'bg-gray-100 text-gray-800';
                                }
                                ?>">
                                <?= ucfirst($usuario['rol_nombre']) ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $usuario['activo'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                <i class="bi bi-<?= $usuario['activo'] ? 'check-circle' : 'x-circle' ?> mr-1"></i>
                                <?= $usuario['activo'] ? 'Activo' : 'Inactivo' ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?= $usuario['ultimo_login'] ? date('d/m/Y H:i', strtotime($usuario['ultimo_login'])) : 'Nunca' ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex space-x-2">
                                <button onclick="editUser(<?= $usuario['id'] ?>)" 
                                        class="text-blue-600 hover:text-blue-900">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button onclick="toggleUserStatus(<?= $usuario['id'] ?>)" 
                                        class="text-<?= $usuario['activo'] ? 'red' : 'green' ?>-600 hover:text-<?= $usuario['activo'] ? 'red' : 'green' ?>-900">
                                    <i class="bi bi-<?= $usuario['activo'] ? 'lock' : 'unlock' ?>"></i>
                                </button>
                                <button onclick="resetPassword(<?= $usuario['id'] ?>)" 
                                        class="text-yellow-600 hover:text-yellow-900">
                                    <i class="bi bi-key"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal para crear/editar usuario -->
<div id="userModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md">
            <div class="px-6 py-4 border-b">
                <h3 class="text-lg font-medium text-gray-900" id="modalTitle">Nuevo Usuario</h3>
            </div>
            <form id="userForm" class="px-6 py-4 space-y-4">
                <input type="hidden" id="userId" name="user_id">
                
                <div>
                    <label class="block text-sm font-medium text-gray-700">Nombre Completo</label>
                    <input type="text" id="nombreCompleto" name="nombre_completo" required
                           class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700">Usuario</label>
                    <input type="text" id="username" name="username" required
                           class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700">Email</label>
                    <input type="email" id="email" name="email" required
                           class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700">Rol</label>
                    <select id="rolId" name="rol_id" required
                            class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
                        <option value="">Seleccionar rol...</option>
                        <?php foreach ($roles as $rol): ?>
                        <option value="<?= $rol['id'] ?>"><?= ucfirst($rol['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div id="passwordFields">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Contraseña</label>
                        <input type="password" id="password" name="password"
                               class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Confirmar Contraseña</label>
                        <input type="password" id="confirmPassword" name="confirm_password"
                               class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
                    </div>
                </div>
                
                <div class="flex items-center justify-end space-x-3 pt-4">
                    <button type="button" onclick="closeUserModal()" 
                            class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                        Cancelar
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                        Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function showCreateUserModal() {
    document.getElementById('modalTitle').textContent = 'Nuevo Usuario';
    document.getElementById('userForm').reset();
    document.getElementById('userId').value = '';
    document.getElementById('password').required = true;
    document.getElementById('confirmPassword').required = true;
    document.getElementById('userModal').classList.remove('hidden');
}

function editUser(userId) {
    // Implementar edición de usuario
    Swal.fire('Info', 'Funcionalidad de edición en desarrollo', 'info');
}

function toggleUserStatus(userId) {
    Swal.fire({
        title: '¿Estás seguro?',
        text: 'Esto cambiará el estado del usuario',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, cambiar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            // Implementar cambio de estado
            Swal.fire('Info', 'Funcionalidad en desarrollo', 'info');
        }
    });
}

function resetPassword(userId) {
    Swal.fire({
        title: '¿Resetear contraseña?',
        text: 'Se generará una nueva contraseña temporal',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, resetear',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            // Implementar reset de password
            Swal.fire('Info', 'Funcionalidad en desarrollo', 'info');
        }
    });
}

function closeUserModal() {
    document.getElementById('userModal').classList.add('hidden');
}

// Manejar envío del formulario
document.getElementById('userForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirmPassword').value;
    
    if (password && password !== confirmPassword) {
        Swal.fire('Error', 'Las contraseñas no coinciden', 'error');
        return;
    }
    
    // Implementar envío del formulario
    Swal.fire('Info', 'Funcionalidad de guardado en desarrollo', 'info');
});

// Cerrar modal al hacer clic fuera
document.getElementById('userModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeUserModal();
    }
});
</script>
