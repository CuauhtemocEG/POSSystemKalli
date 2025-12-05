<div class="text-center py-12">
    <div class="mb-6">
        <i class="bi bi-shield-exclamation text-red-500 text-6xl"></i>
    </div>
    <h1 class="text-3xl font-bold text-white-800 mb-4">Acceso Denegado</h1>
    <p class="text-gray-600 mb-6">No tienes permisos suficientes para acceder a esta sección.</p>
    <div class="space-y-2 text-sm text-gray-500">
        <p><strong>Usuario:</strong> <?= htmlspecialchars($userInfo['username'] ?? 'Desconocido') ?></p>
        <p><strong>Rol:</strong> <?= htmlspecialchars($userInfo['rol'] ?? 'Sin rol') ?></p>
    </div>
    <div class="mt-8">
        <a href="index.php?page=mesas" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg">
            <i class="bi bi-arrow-left mr-2"></i>Volver al Inicio
        </a>
    </div>
</div>
