<?php
// Verificar permisos de administrador
$esAdministrador = false;
if (isset($userInfo)) {
    if (isset($userInfo['rol_id']) && ($userInfo['rol_id'] == 1 || $userInfo['rol_id'] === '1')) {
        $esAdministrador = true;
    } elseif (isset($userInfo['id']) && $userInfo['id'] == 1) {
        $esAdministrador = true;
    }
}

// Obtener tipos/categorías
$tipos = $pdo->query("SELECT id, nombre FROM type ORDER BY nombre")->fetchAll(PDO::FETCH_KEY_PAIR);

// Manejo de mensajes
$message = $_GET['message'] ?? '';
$messageType = $_GET['type'] ?? 'success';
?>

<!-- Mostrar mensajes -->
<?php if ($message): ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        Swal.fire({
            title: '<?= $messageType === 'success' ? '¡Éxito!' : ($messageType === 'error' ? 'Error' : 'Información') ?>',
            text: '<?= htmlspecialchars($message, ENT_QUOTES) ?>',
            icon: '<?= $messageType ?>',
            timer: 3000,
            timerProgressBar: true,
            showConfirmButton: false,
            toast: true,
            position: 'top-end'
        });
        
        // Limpiar URL
        if (window.history.replaceState) {
            const url = new URL(window.location);
            url.searchParams.delete('message');
            url.searchParams.delete('type');
            window.history.replaceState({}, document.title, url.toString());
        }
    });
</script>
<?php endif; ?>

<!-- Header de Página -->
<div class="mb-8 mt-8">
    <div class="bg-dark-700/30 backdrop-blur-xl rounded-2xl border border-dark-600/50 p-6 shadow-xl">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-purple-600 rounded-xl flex items-center justify-center">
                    <i class="bi bi-box-seam text-2xl text-white"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-semibold text-white">Gestión de Productos</h1>
                    <p class="text-gray-400 text-sm">Administra tu catálogo de productos</p>
                </div>
            </div>
            <?php if ($esAdministrador): ?>
            <button onclick="openModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-xl transition-colors font-semibold flex items-center justify-center gap-2 whitespace-nowrap">
                <i class="bi bi-plus-lg"></i>
                <span>Nuevo Producto</span>
            </button>
            <?php endif; ?>
        </div>
    </div>
</div>

    <?php if (!$esAdministrador): ?>
    <!-- Sin permisos -->
    <div class="bg-dark-800/60 backdrop-blur-sm rounded-2xl shadow-xl border border-red-500/30 overflow-hidden">
        <div class="bg-gradient-to-r from-red-600/20 to-orange-600/20 p-6 border-b border-red-500/30">
            <div class="flex items-center gap-3">
                <i class="bi bi-shield-exclamation text-3xl text-red-400"></i>
                <h2 class="text-2xl font-montserrat-bold text-white">Acceso Restringido</h2>
            </div>
        </div>
        <div class="p-12 text-center">
            <div class="max-w-md mx-auto">
                <i class="bi bi-lock text-6xl text-gray-600 mb-6"></i>
                <p class="text-gray-300 text-lg mb-6">No tienes permisos para gestionar productos. Solo los administradores pueden realizar estas acciones.</p>
                <a href="index.php" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-xl transition-colors font-montserrat-semibold">
                    <i class="bi bi-house"></i>
                    Volver al Inicio
                </a>
            </div>
        </div>
    </div>
    <?php else: ?>

<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <!-- Total Productos -->
    <div class="bg-dark-700/30 backdrop-blur-xl rounded-2xl border border-dark-600/50 overflow-hidden">
        <div class="p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-blue-600/20 rounded-xl flex items-center justify-center">
                    <i class="bi bi-box-seam text-2xl text-blue-400"></i>
                </div>
                <div class="text-right">
                    <div class="text-3xl font-bold text-white" id="totalProducts">0</div>
                    <p class="text-gray-400 text-sm">Total</p>
                </div>
            </div>
            <h3 class="text-sm font-medium text-gray-300">Productos Registrados</h3>
        </div>
    </div>

    <!-- Categorías -->
    <div class="bg-dark-700/30 backdrop-blur-xl rounded-2xl border border-dark-600/50 overflow-hidden">
        <div class="p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-purple-600/20 rounded-xl flex items-center justify-center">
                    <i class="bi bi-grid-3x3-gap text-2xl text-purple-400"></i>
                </div>
                <div class="text-right">
                    <div class="text-3xl font-bold text-white"><?= count($tipos) ?></div>
                    <p class="text-gray-400 text-sm">Total</p>
                </div>
            </div>
            <h3 class="text-sm font-medium text-gray-300">Categorías Activas</h3>
        </div>
    </div>

    <!-- Productos Visibles -->
    <div class="bg-dark-700/30 backdrop-blur-xl rounded-2xl border border-dark-600/50 overflow-hidden">
        <div class="p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-green-600/20 rounded-xl flex items-center justify-center">
                    <i class="bi bi-eye text-2xl text-green-400"></i>
                </div>
                <div class="text-right">
                    <div class="text-3xl font-bold text-white" id="visibleProducts">0</div>
                    <p class="text-gray-400 text-sm">Visibles</p>
                </div>
            </div>
            <h3 class="text-sm font-medium text-gray-300">Productos Filtrados</h3>
        </div>
    </div>
</div>

<!-- Filtros y Búsqueda -->
<div class="mb-8">
    <div class="bg-dark-700/30 backdrop-blur-xl rounded-2xl border border-dark-600/50 p-6 shadow-xl">
        <div class="flex items-center mb-4">
            <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-600 rounded-xl flex items-center justify-center mr-3">
                <i class="bi bi-funnel text-white"></i>
            </div>
            <h2 class="text-xl font-semibold text-white">Filtros de Búsqueda</h2>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Búsqueda -->
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">
                    <i class="bi bi-search mr-1"></i>Buscar Producto
                </label>
                <input type="text" 
                       id="searchInput" 
                       class="w-full px-4 py-3 bg-dark-600/50 border border-dark-500/50 rounded-xl text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors" 
                       placeholder="Nombre del producto...">
            </div>
            
            <!-- Filtro de Categoría -->
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">
                    <i class="bi bi-tag mr-1"></i>Categoría
                </label>
                <select id="categoryFilter" 
                        class="w-full px-4 py-3 bg-dark-600/50 border border-dark-500/50 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors cursor-pointer">
                    <option value="">Todas las categorías</option>
                    <?php foreach ($tipos as $id => $nombre): ?>
                        <option value="<?= $id ?>"><?= htmlspecialchars($nombre) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>
</div>

<!-- Grid de Productos -->
<div class="bg-dark-700/30 backdrop-blur-xl rounded-2xl shadow-xl border border-dark-600/50 overflow-hidden">
    <div class="bg-gradient-to-r from-dark-800/80 to-dark-700/80 border-b border-dark-600/50 p-6">
        <div class="flex items-center gap-3">
            <i class="bi bi-grid text-2xl text-blue-400"></i>
            <h2 class="text-xl font-semibold text-white">Catálogo de Productos</h2>
        </div>
    </div>
    
    <div class="p-6">
        <div id="productsGrid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-6">
            <!-- Products will be loaded here -->
        </div>
        
        <!-- No products message -->
        <div id="noProducts" class="text-center py-20 hidden">
            <i class="bi bi-inbox text-6xl text-gray-600 mb-4"></i>
            <p class="text-gray-400 text-xl mb-2">No se encontraron productos</p>
            <p class="text-gray-500 text-sm">Intenta con otros filtros de búsqueda</p>
        </div>
    </div>
</div>

    <?php endif; ?>
    </div>

    <!-- Product Modal -->
    <div id="productModal" class="modal-overlay fixed inset-0 bg-black/70 backdrop-blur-sm z-50 flex items-center justify-center p-4" style="display: none;">
        <div class="modal-content bg-gradient-to-br from-dark-800 to-dark-900 rounded-2xl shadow-2xl w-full max-w-6xl max-h-[90vh] flex flex-col border border-dark-700/50">
            <!-- Modal Header -->
            <div class="bg-gradient-to-r from-blue-600/20 to-purple-600/20 border-b border-blue-500/30 p-6 flex-shrink-0">
                <div class="flex items-center justify-between">
                    <h3 class="text-2xl font-montserrat-bold text-white flex items-center gap-3" id="modalTitle">
                        <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-purple-600 rounded-xl flex items-center justify-center">
                            <i class="bi bi-plus-circle text-xl text-white"></i>
                        </div>
                        <span>Nuevo Producto</span>
                    </h3>
                    <button type="button" onclick="closeModal()" class="text-gray-400 hover:text-white hover:bg-dark-700/50 w-10 h-10 rounded-xl flex items-center justify-center transition-all duration-300 hover:scale-110">
                        <i class="bi bi-x-lg text-xl"></i>
                    </button>
                </div>
            </div>
            
            <form id="productForm" enctype="multipart/form-data" class="flex flex-col flex-1 overflow-hidden">
                <!-- Contenido del Modal: 2 Columnas -->
                <div class="flex-1 overflow-y-auto custom-scrollbar">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-0 min-h-full">
                        <!-- Columna Izquierda: Datos del Formulario -->
                        <div class="p-6 border-r border-dark-700/50">
                            <input type="hidden" id="productId" name="id">
                            <input type="hidden" id="actionType" name="action" value="add">
                            
                            <div class="space-y-6">
                                <!-- Información Básica -->
                                <div class="bg-dark-700/30 rounded-xl p-5 border border-dark-600/30">
                                    <div class="flex items-center gap-2 mb-4">
                                        <i class="bi bi-info-circle text-blue-400 text-lg"></i>
                                        <h4 class="text-base font-montserrat-bold text-white">Información Básica</h4>
                                    </div>
                                    
                                    <div class="space-y-4">
                                        <div>
                                            <label class="block text-sm font-montserrat-semibold text-gray-300 mb-2">
                                                <i class="bi bi-tag mr-2 text-blue-400"></i>
                                                Nombre del Producto *
                                            </label>
                                            <input type="text" id="productName" name="nombre" required 
                                                   class="w-full px-4 py-3 border border-dark-600/50 bg-dark-700/50 text-white rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all font-montserrat-medium placeholder-gray-400 hover:bg-dark-700/70" 
                                                   placeholder="Ej: Pizza Margarita">
                                        </div>
                                        
                                        <div>
                                            <label class="block text-sm font-montserrat-semibold text-gray-300 mb-2">
                                                <i class="bi bi-currency-dollar mr-2 text-green-400"></i>
                                                Precio *
                                            </label>
                                            <div class="relative">
                                                <span class="absolute left-4 top-1/2 transform -translate-y-1/2 text-green-400 font-montserrat-bold text-lg">$</span>
                                                <input type="number" id="productPrice" name="precio" step="0.01" min="0" required 
                                                       class="w-full pl-8 pr-4 py-3 border border-dark-600/50 bg-dark-700/50 text-white rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all font-montserrat-medium placeholder-gray-400 hover:bg-dark-700/70" 
                                                       placeholder="0.00">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Clasificación -->
                                <div class="bg-dark-700/30 rounded-xl p-5 border border-dark-600/30">
                                    <div class="flex items-center gap-2 mb-4">
                                        <i class="bi bi-grid-3x3-gap text-purple-400 text-lg"></i>
                                        <h4 class="text-base font-montserrat-bold text-white">Clasificación</h4>
                                    </div>
                                    
                                    <div class="space-y-4">
                                        <div>
                                            <label class="block text-sm font-montserrat-semibold text-gray-300 mb-2">
                                                <i class="bi bi-folder mr-2 text-purple-400"></i>
                                                Categoría *
                                            </label>
                                            <div class="relative">
                                                <select id="productCategory" name="type" required 
                                                        class="w-full px-4 py-3 border border-dark-600/50 bg-dark-700/50 text-white rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all font-montserrat-medium appearance-none cursor-pointer hover:bg-dark-700/70">
                                                    <option value="">Seleccionar categoría</option>
                                                    <?php foreach ($tipos as $id => $nombre): ?>
                                                        <option value="<?= $id ?>"><?= htmlspecialchars($nombre) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <i class="bi bi-chevron-down absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-400 pointer-events-none"></i>
                                            </div>
                                        </div>
                                        
                                        <div>
                                            <label class="block text-sm font-montserrat-semibold text-gray-300 mb-2">
                                                <i class="bi bi-card-text mr-2 text-green-400"></i>
                                                Descripción
                                            </label>
                                            <textarea id="productDescription" name="descripcion" rows="6" 
                                                      class="w-full px-4 py-3 border border-dark-600/50 bg-dark-700/50 text-white rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all font-montserrat-medium resize-none placeholder-gray-400 hover:bg-dark-700/70" 
                                                      placeholder="Descripción opcional del producto..."></textarea>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Variedades -->
                                <div class="bg-dark-700/30 rounded-xl p-5 border border-dark-600/30">
                                    <div class="flex items-center justify-between mb-4">
                                        <div class="flex items-center gap-2">
                                            <i class="bi bi-list-ul text-orange-400 text-lg"></i>
                                            <h4 class="text-base font-montserrat-bold text-white">Variedades del Producto</h4>
                                        </div>
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="checkbox" id="tieneVariedades" class="sr-only peer">
                                            <div class="w-11 h-6 bg-gray-700 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-orange-500 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-orange-600"></div>
                                            <span class="ms-3 text-sm font-medium text-gray-300">¿Tiene variedades?</span>
                                        </label>
                                    </div>
                                    
                                    <div id="variedadesContainer" class="hidden space-y-4">
                                        <div class="text-sm text-gray-400 mb-3">
                                            <i class="bi bi-info-circle mr-1"></i>
                                            Agrega grupos de opciones (ej: Salsa: Roja, Verde)
                                        </div>
                                        
                                        <!-- Lista de grupos de variedades -->
                                        <div id="gruposVariedadesList" class="space-y-3">
                                            <!-- Los grupos se agregarán dinámicamente aquí -->
                                        </div>
                                        
                                        <!-- Botón para agregar nuevo grupo -->
                                        <button type="button" onclick="agregarGrupoVariedad()" class="w-full px-4 py-3 bg-orange-600/20 hover:bg-orange-600/30 border border-orange-500/30 text-orange-400 rounded-xl transition-all font-montserrat-semibold flex items-center justify-center gap-2">
                                            <i class="bi bi-plus-circle text-lg"></i>
                                            <span>Agregar Grupo de Variedad</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Columna Derecha: TODO relacionado a Imagen -->
                        <div class="p-6 bg-dark-900/40 flex flex-col">
                            <div class="flex items-center gap-2 mb-4">
                                <i class="bi bi-image text-yellow-400 text-lg"></i>
                                <h4 class="text-base font-montserrat-bold text-white">Imagen del Producto</h4>
                            </div>
                            
                            <!-- Input oculto para seleccionar archivo -->
                            <input type="file" id="imageInput" name="imagen" accept="image/*" class="hidden">
                            
                            <!-- Área de upload y preview -->
                            <div class="flex-1 flex flex-col gap-4">
                                <!-- Botón para seleccionar imagen -->
                                <div class="file-upload-area border-2 border-dashed border-dark-600/50 bg-dark-700/20 rounded-xl p-6 text-center cursor-pointer hover:border-blue-400 hover:bg-dark-700/40 transition-all duration-300" id="uploadAreaClick">
                                    <div class="w-16 h-16 bg-gradient-to-br from-blue-600/20 to-purple-600/20 rounded-2xl flex items-center justify-center mx-auto mb-3">
                                        <i class="bi bi-cloud-upload text-3xl text-blue-400"></i>
                                    </div>
                                    <div class="text-base font-montserrat-semibold text-gray-300 mb-2">Seleccionar imagen</div>
                                    <div class="text-sm text-gray-500 font-montserrat-regular">Click aquí o arrastra y suelta<br>JPG, PNG, GIF, WEBP - Máx 20MB</div>
                                </div>
                                
                                <!-- Contenedor de Preview -->
                                <div class="flex-1 flex items-center justify-center min-h-[300px]">
                                    <!-- Placeholder cuando no hay imagen -->
                                    <div id="uploadPlaceholderPreview" class="text-center">
                                        <div class="w-32 h-32 bg-gradient-to-br from-gray-700/50 to-gray-800/50 rounded-3xl flex items-center justify-center mx-auto mb-6 border-2 border-dashed border-gray-600">
                                            <i class="bi bi-image text-6xl text-gray-600"></i>
                                        </div>
                                        <p class="text-gray-500 font-montserrat-medium text-lg mb-2">Sin imagen seleccionada</p>
                                        <p class="text-gray-600 text-sm font-montserrat-regular">La imagen aparecerá aquí</p>
                                    </div>
                                    
                                    <!-- Vista previa de la imagen -->
                                    <div id="imagePreview" class="hidden w-full h-full flex flex-col items-center justify-center">
                                        <div class="relative group">
                                            <img id="previewImg" class="max-w-full max-h-[400px] rounded-2xl shadow-2xl border-2 border-blue-500/30 object-contain" alt="Preview">
                                            <div class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition-opacity duration-300 rounded-2xl flex items-center justify-center">
                                                <button type="button" onclick="removeImage(); event.stopPropagation();" class="bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-xl transition-all duration-300 font-montserrat-semibold shadow-lg hover:shadow-xl hover:scale-105 inline-flex items-center gap-2">
                                                    <i class="bi bi-trash text-lg"></i>
                                                    <span>Remover</span>
                                                </button>
                                            </div>
                                        </div>
                                        <div id="imageInfo" class="mt-4 text-center">
                                            <p class="text-gray-400 text-sm font-montserrat-medium" id="imageFileName"></p>
                                            <p class="text-gray-500 text-xs font-montserrat-regular" id="imageFileSize"></p>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Info de imagen actual al editar -->
                                <div id="currentImageInfo" class="hidden p-3 bg-blue-600/10 rounded-xl border border-blue-500/30">
                                    <div class="flex items-center gap-2">
                                        <i class="bi bi-info-circle text-blue-400 text-sm"></i>
                                        <span class="text-gray-300 font-montserrat-medium text-sm">Imagen actual: <span id="currentImageName" class="font-montserrat-bold text-blue-400"></span></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Modal Footer -->
                <div class="bg-gradient-to-r from-dark-900/80 to-dark-800/80 border-t border-dark-700/50 p-6 flex flex-col sm:flex-row justify-end gap-3 flex-shrink-0">
                    <button type="button" onclick="closeModal()" class="px-6 py-3 bg-gray-700 hover:bg-gray-600 text-white rounded-xl transition-all duration-300 font-montserrat-semibold shadow-md hover:shadow-lg flex items-center justify-center gap-2">
                        <i class="bi bi-x-circle"></i>
                        Cancelar
                    </button>
                    <button type="submit" id="submitBtn" class="px-6 py-3 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white rounded-xl transition-all duration-300 font-montserrat-semibold shadow-md hover:shadow-lg hover:scale-105 flex items-center justify-center gap-2">
                        <i class="bi bi-check-lg text-xl"></i>
                        <span id="submitText">Crear Producto</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Global variables
        let allProducts = [];
        let filteredProducts = [];
        let currentEditId = null;
        let currentImageFile = null;
        let isEditing = false;
        let gruposVariedadesCounter = 0; // Contador para IDs únicos de grupos
        let gruposVariedadesData = []; // Array para guardar datos de grupos y opciones

        // Initialize app
        document.addEventListener('DOMContentLoaded', function() {
            loadProducts();
            initializeEventListeners();
            
            // Listener para el toggle de variedades
            document.getElementById('tieneVariedades').addEventListener('change', function(e) {
                const container = document.getElementById('variedadesContainer');
                if (e.target.checked) {
                    container.classList.remove('hidden');
                } else {
                    container.classList.add('hidden');
                }
            });
        });

        // Load products from API
        async function loadProducts() {
            try {
                const response = await fetch('./api/productController/api.php?action=get_products&limit=1000', {
                    headers: {
                        'Accept': 'application/json'
                    }
                });
                const data = await response.json();
                
                if (data.success) {
                    allProducts = data.products || [];
                    filteredProducts = [...allProducts];
                    renderProducts();
                    updateStats();
                    console.log(`Total productos cargados: ${allProducts.length}`);
                } else {
                    console.error('Error loading products:', data.message);
                    showAlert('Error al cargar productos', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showAlert('Error de conexión al cargar productos', 'error');
            }
        }

        // Initialize event listeners
        function initializeEventListeners() {
            // Search functionality
            const searchInput = document.getElementById('searchInput');
            searchInput.addEventListener('input', handleSearch);
            
            // Category filter
            const categoryFilter = document.getElementById('categoryFilter');
            categoryFilter.addEventListener('change', handleCategoryFilter);
            
            // Form submission
            const productForm = document.getElementById('productForm');
            productForm.addEventListener('submit', handleFormSubmit);
            
            // Image input change
            const imageInput = document.getElementById('imageInput');
            imageInput.addEventListener('change', handleImageChange);
            
            // Click en área de upload para abrir selector de archivos
            const uploadArea = document.getElementById('uploadAreaClick');
            if (uploadArea) {
                uploadArea.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    document.getElementById('imageInput').click();
                });
                
                // Drag and drop for file upload
                uploadArea.addEventListener('dragover', handleDragOver);
                uploadArea.addEventListener('dragleave', handleDragLeave);
                uploadArea.addEventListener('drop', handleDrop);
            }
        }

        // Render products in grid
        function renderProducts() {
            const productsGrid = document.getElementById('productsGrid');
            const noProducts = document.getElementById('noProducts');
            
            if (filteredProducts.length === 0) {
                productsGrid.innerHTML = '';
                noProducts.classList.remove('hidden');
                return;
            }
            
            noProducts.classList.add('hidden');
            
            const html = filteredProducts.map(product => {
                const categoryName = getCategoryName(product.type);
                const imageUrl = product.imagen ? `./assets/img/${product.imagen}` : null;
                
                return `
                    <div class="bg-dark-600/30 rounded-xl overflow-hidden border border-dark-500/50 hover:border-blue-500/50 transition-all hover:shadow-xl">
                        <!-- Imagen del producto -->
                        <div class="relative h-48 bg-dark-700/50">
                            ${imageUrl ? 
                                `<img src="${imageUrl}" alt="${product.nombre}" class="w-full h-full object-cover" onerror="this.style.display='none'; this.nextElementSibling.classList.remove('hidden');">
                                 <div class="hidden absolute inset-0 bg-gradient-to-br from-slate-600/40 to-slate-800/60 flex flex-col items-center justify-center">
                                     <i class="bi bi-image text-5xl text-gray-400 mb-2"></i>
                                     <p class="text-gray-400 text-sm font-semibold">Sin imagen</p>
                                 </div>` :
                                `<div class="absolute inset-0 bg-gradient-to-br from-slate-600/40 to-slate-800/60 flex flex-col items-center justify-center">
                                     <i class="bi bi-image text-5xl text-gray-400 mb-2"></i>
                                     <p class="text-gray-400 text-sm font-semibold">Sin imagen</p>
                                 </div>`
                            }
                            
                            <!-- Categoría Badge -->
                            <div class="absolute top-3 right-3">
                                <span class="bg-blue-600 text-white px-3 py-1 rounded-full text-xs font-semibold">
                                    ${categoryName}
                                </span>
                            </div>
                        </div>
                        
                        <!-- Contenido de la tarjeta -->
                        <div class="p-4">
                            <h3 class="text-white font-semibold text-lg mb-2 truncate">${product.nombre}</h3>
                            
                            ${product.descripcion ? 
                                `<p class="text-gray-400 text-sm mb-3 line-clamp-2">${product.descripcion}</p>` : 
                                `<p class="text-gray-600 text-sm mb-3 italic">Sin descripción</p>`
                            }
                            
                            <!-- Precio -->
                            <div class="mb-3">
                                <span class="text-green-400 font-bold text-2xl">$${parseFloat(product.precio).toFixed(2)}</span>
                            </div>
                            
                            <!-- Acciones -->
                            <div class="grid grid-cols-2 gap-2">
                                <button onclick="editProduct(${product.id})" class="px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors text-sm font-medium flex items-center justify-center gap-1">
                                    <i class="bi bi-pencil"></i>
                                    Editar
                                </button>
                                <button onclick="deleteProduct(${product.id})" class="px-3 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors text-sm font-medium flex items-center justify-center gap-1">
                                    <i class="bi bi-trash"></i>
                                    Eliminar
                                </button>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
            
            productsGrid.innerHTML = html;
        }

        // Get category name by ID
        function getCategoryName(typeId) {
            const categorySelect = document.getElementById('categoryFilter');
            const option = categorySelect.querySelector(`option[value="${typeId}"]`);
            return option ? option.textContent : 'Sin categoría';
        }

        // Update statistics
        function updateStats() {
            document.getElementById('totalProducts').textContent = allProducts.length;
            document.getElementById('visibleProducts').textContent = filteredProducts.length;
        }

        // Search functionality
        function handleSearch(e) {
            const searchTerm = e.target.value.toLowerCase().trim();
            applyFilters(searchTerm, document.getElementById('categoryFilter').value);
        }

        // Category filter
        function handleCategoryFilter(e) {
            const categoryId = e.target.value;
            const searchTerm = document.getElementById('searchInput').value.toLowerCase().trim();
            applyFilters(searchTerm, categoryId);
        }

        // Apply filters
        function applyFilters(searchTerm, categoryId) {
            filteredProducts = allProducts.filter(product => {
                const matchesSearch = !searchTerm || 
                    product.nombre.toLowerCase().includes(searchTerm) ||
                    (product.descripcion && product.descripcion.toLowerCase().includes(searchTerm));
                
                const matchesCategory = !categoryId || product.type == categoryId;
                
                return matchesSearch && matchesCategory;
            });
            
            renderProducts();
            updateStats();
        }

        // Modal functions
        function openModal() {
            resetForm();
            isEditing = false;
            currentEditId = null;
            
            document.getElementById('modalTitle').innerHTML = '<div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-purple-600 rounded-xl flex items-center justify-center"><i class="bi bi-plus-circle text-xl text-white"></i></div><span>Nuevo Producto</span>';
            document.getElementById('submitText').textContent = 'Crear Producto';
            document.getElementById('actionType').value = 'add';
            
            const modal = document.getElementById('productModal');
            modal.style.display = 'flex';
            setTimeout(() => modal.classList.add('show'), 10);
            document.body.style.overflow = 'hidden';
        }

        function closeModal() {
            const modal = document.getElementById('productModal');
            modal.classList.remove('show');
            document.body.style.overflow = '';
            
            setTimeout(() => {
                modal.style.display = 'none';
                resetForm();
            }, 200);
        }

        // Edit product
        async function editProduct(id) {
            try {
                const product = allProducts.find(p => p.id == id);
                if (!product) {
                    showAlert('Producto no encontrado', 'error');
                    return;
                }
                
                isEditing = true;
                currentEditId = id;
                
                // Fill form
                document.getElementById('productId').value = product.id;
                document.getElementById('productName').value = product.nombre;
                document.getElementById('productPrice').value = product.precio;
                document.getElementById('productCategory').value = product.type;
                document.getElementById('productDescription').value = product.descripcion || '';
                document.getElementById('actionType').value = 'edit';
                
                // Show current image info if exists
                if (product.imagen) {
                    const currentImageInfo = document.getElementById('currentImageInfo');
                    const currentImageName = document.getElementById('currentImageName');
                    currentImageName.textContent = product.imagen;
                    currentImageInfo.classList.remove('hidden');
                }
                
                // Cargar variedades del producto
                if (product.tiene_variedades == 1 || product.tiene_variedades === '1' || product.tiene_variedades === true) {
                    await cargarVariedadesProducto(id);
                } else {
                    // Resetear variedades si no las tiene
                    document.getElementById('tieneVariedades').checked = false;
                    document.getElementById('variedadesContainer').classList.add('hidden');
                    document.getElementById('gruposVariedadesList').innerHTML = '';
                    gruposVariedadesCounter = 0;
                }
                
                // Update modal
                document.getElementById('modalTitle').innerHTML = '<div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-blue-600 rounded-xl flex items-center justify-center"><i class="bi bi-pencil text-xl text-white"></i></div><span>Editar Producto</span>';
                document.getElementById('submitText').textContent = 'Actualizar Producto';
                
                // Show modal
                const modal = document.getElementById('productModal');
                modal.style.display = 'flex';
                setTimeout(() => modal.classList.add('show'), 10);
                document.body.style.overflow = 'hidden';
                
            } catch (error) {
                console.error('Error:', error);
                showAlert('Error al cargar datos del producto', 'error');
            }
        }

        // Delete product
        async function deleteProduct(id) {
            const product = allProducts.find(p => p.id == id);
            if (!product) return;
            
            const result = await Swal.fire({
                title: '¿Eliminar producto?',
                text: `Se eliminará "${product.nombre}" permanentemente.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            });
            
            if (result.isConfirmed) {
                try {
                    const formData = new FormData();
                    formData.append('action', 'delete');
                    formData.append('id', id);
                    
                    const response = await fetch('./api/productController/crud.php', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        showAlert('Producto eliminado exitosamente', 'success');
                        loadProducts();
                    } else {
                        showAlert(data.message || 'Error al eliminar el producto', 'error');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    showAlert('Error de conexión al eliminar producto', 'error');
                }
            }
        }

        // Handle form submission
        async function handleFormSubmit(e) {
            e.preventDefault();
            
            const submitBtn = document.getElementById('submitBtn');
            const submitText = document.getElementById('submitText');
            const originalText = submitText.textContent;
            
            // Show loading state
            submitBtn.disabled = true;
            submitText.innerHTML = '<i class="bi bi-arrow-clockwise loading"></i> Procesando...';
            
            try {
                const formData = new FormData(e.target);
                
                // Recopilar datos de variedades
                const variedades = recopilarDatosVariedades();
                if (variedades.length > 0) {
                    formData.append('variedades', JSON.stringify(variedades));
                    formData.set('tiene_variedades', '1');
                } else {
                    formData.set('tiene_variedades', '0');
                }
                
                // Log para debug
                console.log('Enviando formulario...');
                console.log('Acción:', formData.get('action'));
                console.log('Nombre:', formData.get('nombre'));
                console.log('Precio:', formData.get('precio'));
                console.log('Tipo:', formData.get('type'));
                console.log('Tiene variedades:', formData.get('tiene_variedades'));
                console.log('Variedades:', variedades);
                
                // Verificar si hay archivo en el input
                const imageInput = document.getElementById('imageInput');
                if (imageInput.files && imageInput.files.length > 0) {
                    console.log('Archivo en input:', imageInput.files[0].name, imageInput.files[0].size);
                    // El archivo ya está en el FormData desde el input, no necesitamos agregarlo manualmente
                } else if (currentImageFile) {
                    console.log('Usando currentImageFile:', currentImageFile.name, currentImageFile.size);
                    formData.set('imagen', currentImageFile);
                } else {
                    console.log('No hay imagen seleccionada');
                }
                
                // Log de todos los campos del FormData
                for (let pair of formData.entries()) {
                    if (pair[1] instanceof File) {
                        console.log(pair[0] + ': [File] ' + pair[1].name + ' (' + pair[1].size + ' bytes)');
                    } else {
                        console.log(pair[0] + ': ' + pair[1]);
                    }
                }
                
                const response = await fetch('./api/productController/crud.php', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });
                
                console.log('Response status:', response.status);
                const data = await response.json();
                console.log('Response data:', data);
                
                if (data.success) {
                    const action = isEditing ? 'actualizado' : 'creado';
                    showAlert(`Producto ${action} exitosamente`, 'success');
                    closeModal();
                    loadProducts();
                } else {
                    showAlert(data.message || 'Error al procesar la solicitud', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showAlert('Error de conexión al procesar solicitud', 'error');
            } finally {
                // Restore button state
                submitBtn.disabled = false;
                submitText.textContent = originalText;
            }
        }

        // Handle image selection
        function handleImageChange(e) {
            const file = e.target.files[0];
            if (file) {
                processImageFile(file);
            }
        }

        // Process image file
        function processImageFile(file) {
            // Validate file type
            const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            if (!validTypes.includes(file.type.toLowerCase())) {
                showAlert('Tipo de archivo no válido. Solo se permiten: JPG, PNG, GIF, WEBP', 'error');
                return;
            }
            
            // Validate file size (20MB max)
            const maxSize = 20 * 1024 * 1024; // 20MB
            if (file.size > maxSize) {
                const sizeMB = (file.size / (1024 * 1024)).toFixed(2);
                showAlert(`El archivo es demasiado grande (${sizeMB}MB). Máximo permitido: 20MB`, 'error');
                return;
            }
            
            // Check if file is actually an image by creating a temporary image
            const img = new Image();
            img.onload = function() {
                // Valid image, proceed with processing
                processValidImage(file);
            };
            img.onerror = function() {
                showAlert('El archivo no es una imagen válida', 'error');
                return;
            };
            
            // Create object URL to test image
            const objectUrl = URL.createObjectURL(file);
            img.src = objectUrl;
        }
        
        // Process valid image file
        function processValidImage(file) {
            // Check if compression is needed (file > 5MB)
            if (file.size > 5 * 1024 * 1024) {
                showAlert('Comprimiendo imagen, por favor espera...', 'info');
                compressImage(file, 0.8).then(compressedFile => {
                    currentImageFile = compressedFile;
                    showImagePreview(compressedFile);
                    
                    const originalSize = (file.size / 1024 / 1024).toFixed(2);
                    const newSize = (compressedFile.size / 1024 / 1024).toFixed(2);
                    showAlert(`Imagen comprimida: ${originalSize}MB → ${newSize}MB`, 'success');
                });
            } else {
                currentImageFile = file;
                showImagePreview(file);
            }
        }
        
        // Compress image function
        function compressImage(file, quality = 0.8) {
            return new Promise((resolve) => {
                const canvas = document.createElement('canvas');
                const ctx = canvas.getContext('2d');
                const img = new Image();
                
                img.onload = function() {
                    // Calculate new dimensions with progressive sizing based on file size
                    let maxSize = 1920; // Default for files 5-10MB
                    
                    if (file.size > 15 * 1024 * 1024) { // >15MB
                        maxSize = 1280;
                        quality = 0.7; // Lower quality for very large files
                    } else if (file.size > 10 * 1024 * 1024) { // >10MB
                        maxSize = 1600;
                        quality = 0.75;
                    }
                    
                    let { width, height } = img;
                    
                    if (width > height) {
                        if (width > maxSize) {
                            height = (height * maxSize) / width;
                            width = maxSize;
                        }
                    } else {
                        if (height > maxSize) {
                            width = (width * maxSize) / height;
                            height = maxSize;
                        }
                    }
                    
                    canvas.width = width;
                    canvas.height = height;
                    
                    // Draw and compress
                    ctx.drawImage(img, 0, 0, width, height);
                    
                    canvas.toBlob((blob) => {
                        // Create new file object
                        const compressedFile = new File([blob], file.name, {
                            type: 'image/jpeg',
                            lastModified: Date.now()
                        });
                        resolve(compressedFile);
                    }, 'image/jpeg', quality);
                };
                
                img.src = URL.createObjectURL(file);
            });
        }
        
        // Show image preview
        function showImagePreview(file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const previewImg = document.getElementById('previewImg');
                const imagePreview = document.getElementById('imagePreview');
                const uploadPlaceholder = document.getElementById('uploadPlaceholderPreview');
                const imageFileName = document.getElementById('imageFileName');
                const imageFileSize = document.getElementById('imageFileSize');
                
                // Establecer la imagen
                previewImg.src = e.target.result;
                
                // Mostrar información del archivo
                imageFileName.textContent = file.name;
                const sizeInMB = (file.size / (1024 * 1024)).toFixed(2);
                imageFileSize.textContent = `Tamaño: ${sizeInMB} MB`;
                
                // Cambiar vistas
                imagePreview.classList.remove('hidden');
                uploadPlaceholder.classList.add('hidden');
            };
            reader.readAsDataURL(file);
        }

        // Remove image
        function removeImage() {
            currentImageFile = null;
            document.getElementById('imageInput').value = '';
            document.getElementById('imagePreview').classList.add('hidden');
            document.getElementById('uploadPlaceholderPreview').classList.remove('hidden');
        }

        // Drag and drop handlers
        function handleDragOver(e) {
            e.preventDefault();
            e.stopPropagation();
            e.currentTarget.classList.add('dragover');
        }

        function handleDragLeave(e) {
            e.preventDefault();
            e.stopPropagation();
            e.currentTarget.classList.remove('dragover');
        }

        function handleDrop(e) {
            e.preventDefault();
            e.stopPropagation();
            e.currentTarget.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                // Actualizar el input file con el archivo arrastrado
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(files[0]);
                document.getElementById('imageInput').files = dataTransfer.files;
                
                // Procesar la imagen
                processImageFile(files[0]);
            }
        }

        // Reset form
        function resetForm() {
            document.getElementById('productForm').reset();
            document.getElementById('productId').value = '';
            document.getElementById('actionType').value = 'add';
            
            // Reset image preview
            document.getElementById('imagePreview').classList.add('hidden');
            document.getElementById('uploadPlaceholderPreview').classList.remove('hidden');
            document.getElementById('currentImageInfo').classList.add('hidden');
            
            currentImageFile = null;
            
            // Reset variedades
            document.getElementById('tieneVariedades').checked = false;
            document.getElementById('variedadesContainer').classList.add('hidden');
            document.getElementById('gruposVariedadesList').innerHTML = '';
            gruposVariedadesCounter = 0;
        }

        // Show alert
        function showAlert(message, type = 'info') {
            Swal.fire({
                title: type === 'success' ? '¡Éxito!' : (type === 'error' ? 'Error' : 'Información'),
                text: message,
                icon: type,
                timer: 3000,
                timerProgressBar: true,
                showConfirmButton: false,
                toast: true,
                position: 'top-end'
            });
        }

        // ===================================
        // FUNCIONES DE VARIEDADES
        // ===================================
        
        // Agregar un nuevo grupo de variedades
        function agregarGrupoVariedad() {
            gruposVariedadesCounter++;
            const grupoId = `grupo_${gruposVariedadesCounter}`;
            
            const grupoHTML = `
                <div class="bg-dark-800/50 rounded-xl p-4 border border-dark-600/30" id="${grupoId}">
                    <div class="flex items-center justify-between mb-3">
                        <h5 class="text-sm font-montserrat-bold text-orange-400">Grupo ${gruposVariedadesCounter}</h5>
                        <button type="button" onclick="eliminarGrupoVariedad('${grupoId}')" class="text-red-400 hover:text-red-300 transition-colors">
                            <i class="bi bi-trash text-lg"></i>
                        </button>
                    </div>
                    
                    <div class="space-y-3">
                        <!-- Nombre del grupo -->
                        <div>
                            <label class="block text-xs font-montserrat-semibold text-gray-400 mb-1">
                                Nombre del Grupo (ej: Salsa, Ingrediente)
                            </label>
                            <input type="text" 
                                   class="grupo-nombre w-full px-3 py-2 bg-dark-700/50 border border-dark-600/50 text-white rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 text-sm" 
                                   placeholder="Ej: Salsa"
                                   data-grupo-id="${grupoId}">
                        </div>
                        
                        <!-- Obligatorio -->
                        <div class="flex items-center gap-2">
                            <input type="checkbox" id="${grupoId}_obligatorio" class="grupo-obligatorio w-4 h-4 text-orange-600 bg-dark-700 border-dark-600 rounded focus:ring-orange-500" checked>
                            <label for="${grupoId}_obligatorio" class="text-xs text-gray-400 cursor-pointer">Selección obligatoria</label>
                        </div>
                        
                        <!-- Opciones del grupo -->
                        <div class="bg-dark-900/50 rounded-lg p-3 border border-dark-700/30">
                            <div class="flex items-center justify-between mb-2">
                                <label class="text-xs font-montserrat-semibold text-gray-400">Opciones</label>
                                <button type="button" onclick="agregarOpcionVariedad('${grupoId}')" class="text-xs text-orange-400 hover:text-orange-300 transition-colors flex items-center gap-1">
                                    <i class="bi bi-plus-circle"></i>
                                    Agregar Opción
                                </button>
                            </div>
                            <div id="${grupoId}_opciones" class="space-y-2">
                                <!-- Las opciones se agregarán aquí -->
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            document.getElementById('gruposVariedadesList').insertAdjacentHTML('beforeend', grupoHTML);
            
            // Agregar al menos una opción por defecto
            agregarOpcionVariedad(grupoId);
        }
        
        // Eliminar un grupo de variedades
        function eliminarGrupoVariedad(grupoId) {
            Swal.fire({
                title: '¿Eliminar grupo?',
                text: 'Se eliminarán todas las opciones de este grupo',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById(grupoId).remove();
                }
            });
        }
        
        // Agregar una opción a un grupo de variedades
        function agregarOpcionVariedad(grupoId) {
            const opcionesContainer = document.getElementById(`${grupoId}_opciones`);
            const opcionCount = opcionesContainer.children.length + 1;
            const opcionId = `${grupoId}_opcion_${opcionCount}`;
            
            const opcionHTML = `
                <div class="flex items-center gap-2" id="${opcionId}">
                    <input type="text" 
                           class="opcion-nombre flex-1 px-3 py-2 bg-dark-800/50 border border-dark-600/30 text-white rounded-lg focus:ring-2 focus:ring-orange-500 text-sm" 
                           placeholder="Ej: Roja"
                           data-grupo-id="${grupoId}">
                    <div class="flex items-center gap-1">
                        <span class="text-xs text-gray-500">$</span>
                        <input type="number" 
                               class="opcion-precio w-20 px-2 py-2 bg-dark-800/50 border border-dark-600/30 text-white rounded-lg focus:ring-2 focus:ring-orange-500 text-sm" 
                               placeholder="0.00"
                               step="0.01"
                               min="0"
                               value="0"
                               data-grupo-id="${grupoId}">
                    </div>
                    <button type="button" onclick="eliminarOpcionVariedad('${opcionId}')" class="text-red-400 hover:text-red-300 transition-colors p-1">
                        <i class="bi bi-x-circle"></i>
                    </button>
                </div>
            `;
            
            opcionesContainer.insertAdjacentHTML('beforeend', opcionHTML);
        }
        
        // Eliminar una opción de variedad
        function eliminarOpcionVariedad(opcionId) {
            document.getElementById(opcionId).remove();
        }
        
        // Recopilar datos de variedades del formulario
        function recopilarDatosVariedades() {
            const tieneVariedades = document.getElementById('tieneVariedades').checked;
            
            if (!tieneVariedades) {
                return [];
            }
            
            const grupos = [];
            const gruposElements = document.querySelectorAll('#gruposVariedadesList > div');
            
            gruposElements.forEach((grupoElement, index) => {
                const grupoId = grupoElement.id;
                const nombreInput = grupoElement.querySelector('.grupo-nombre');
                const obligatorioCheckbox = grupoElement.querySelector('.grupo-obligatorio');
                const nombreGrupo = nombreInput ? nombreInput.value.trim() : '';
                
                if (!nombreGrupo) return; // Saltar grupos sin nombre
                
                const opciones = [];
                const opcionesElements = grupoElement.querySelectorAll(`#${grupoId}_opciones .opcion-nombre`);
                
                opcionesElements.forEach((opcionInput) => {
                    const nombreOpcion = opcionInput.value.trim();
                    if (!nombreOpcion) return; // Saltar opciones sin nombre
                    
                    const precioInput = opcionInput.parentElement.querySelector('.opcion-precio');
                    const precio = precioInput ? parseFloat(precioInput.value) || 0 : 0;
                    
                    opciones.push({
                        nombre: nombreOpcion,
                        precio_adicional: precio
                    });
                });
                
                if (opciones.length > 0) {
                    grupos.push({
                        nombre: nombreGrupo,
                        obligatorio: obligatorioCheckbox ? (obligatorioCheckbox.checked ? 1 : 0) : 1,
                        orden: index + 1,
                        opciones: opciones
                    });
                }
            });
            
            return grupos;
        }
        
        // Cargar variedades al editar un producto
        async function cargarVariedadesProducto(productoId) {
            try {
                const response = await fetch(`./api/productController/api.php?action=get_variedades&producto_id=${productoId}`);
                const data = await response.json();
                
                if (data.success && data.variedades && data.variedades.length > 0) {
                    // Activar el toggle
                    document.getElementById('tieneVariedades').checked = true;
                    document.getElementById('variedadesContainer').classList.remove('hidden');
                    
                    // Limpiar grupos existentes
                    document.getElementById('gruposVariedadesList').innerHTML = '';
                    gruposVariedadesCounter = 0;
                    
                    // Cargar cada grupo con sus opciones
                    data.variedades.forEach(grupo => {
                        gruposVariedadesCounter++;
                        const grupoId = `grupo_${gruposVariedadesCounter}`;
                        
                        const grupoHTML = `
                            <div class="bg-dark-800/50 rounded-xl p-4 border border-dark-600/30" id="${grupoId}" data-grupo-db-id="${grupo.id}">
                                <div class="flex items-center justify-between mb-3">
                                    <h5 class="text-sm font-montserrat-bold text-orange-400">Grupo ${gruposVariedadesCounter}</h5>
                                    <button type="button" onclick="eliminarGrupoVariedad('${grupoId}')" class="text-red-400 hover:text-red-300 transition-colors">
                                        <i class="bi bi-trash text-lg"></i>
                                    </button>
                                </div>
                                
                                <div class="space-y-3">
                                    <div>
                                        <label class="block text-xs font-montserrat-semibold text-gray-400 mb-1">
                                            Nombre del Grupo (ej: Salsa, Ingrediente)
                                        </label>
                                        <input type="text" 
                                               class="grupo-nombre w-full px-3 py-2 bg-dark-700/50 border border-dark-600/50 text-white rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 text-sm" 
                                               placeholder="Ej: Salsa"
                                               value="${grupo.nombre}"
                                               data-grupo-id="${grupoId}">
                                    </div>
                                    
                                    <div class="flex items-center gap-2">
                                        <input type="checkbox" id="${grupoId}_obligatorio" class="grupo-obligatorio w-4 h-4 text-orange-600 bg-dark-700 border-dark-600 rounded focus:ring-orange-500" ${grupo.obligatorio ? 'checked' : ''}>
                                        <label for="${grupoId}_obligatorio" class="text-xs text-gray-400 cursor-pointer">Selección obligatoria</label>
                                    </div>
                                    
                                    <div class="bg-dark-900/50 rounded-lg p-3 border border-dark-700/30">
                                        <div class="flex items-center justify-between mb-2">
                                            <label class="text-xs font-montserrat-semibold text-gray-400">Opciones</label>
                                            <button type="button" onclick="agregarOpcionVariedad('${grupoId}')" class="text-xs text-orange-400 hover:text-orange-300 transition-colors flex items-center gap-1">
                                                <i class="bi bi-plus-circle"></i>
                                                Agregar Opción
                                            </button>
                                        </div>
                                        <div id="${grupoId}_opciones" class="space-y-2">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                        
                        document.getElementById('gruposVariedadesList').insertAdjacentHTML('beforeend', grupoHTML);
                        
                        // Cargar opciones de este grupo
                        if (grupo.opciones && grupo.opciones.length > 0) {
                            grupo.opciones.forEach(opcion => {
                                const opcionesContainer = document.getElementById(`${grupoId}_opciones`);
                                const opcionCount = opcionesContainer.children.length + 1;
                                const opcionId = `${grupoId}_opcion_${opcionCount}`;
                                
                                const opcionHTML = `
                                    <div class="flex items-center gap-2" id="${opcionId}" data-opcion-db-id="${opcion.id}">
                                        <input type="text" 
                                               class="opcion-nombre flex-1 px-3 py-2 bg-dark-800/50 border border-dark-600/30 text-white rounded-lg focus:ring-2 focus:ring-orange-500 text-sm" 
                                               placeholder="Ej: Roja"
                                               value="${opcion.nombre}"
                                               data-grupo-id="${grupoId}">
                                        <div class="flex items-center gap-1">
                                            <span class="text-xs text-gray-500">$</span>
                                            <input type="number" 
                                                   class="opcion-precio w-20 px-2 py-2 bg-dark-800/50 border border-dark-600/30 text-white rounded-lg focus:ring-2 focus:ring-orange-500 text-sm" 
                                                   placeholder="0.00"
                                                   step="0.01"
                                                   min="0"
                                                   value="${opcion.precio_adicional}"
                                                   data-grupo-id="${grupoId}">
                                        </div>
                                        <button type="button" onclick="eliminarOpcionVariedad('${opcionId}')" class="text-red-400 hover:text-red-300 transition-colors p-1">
                                            <i class="bi bi-x-circle"></i>
                                        </button>
                                    </div>
                                `;
                                
                                opcionesContainer.insertAdjacentHTML('beforeend', opcionHTML);
                            });
                        }
                    });
                } else {
                    // No tiene variedades, resetear
                    document.getElementById('tieneVariedades').checked = false;
                    document.getElementById('variedadesContainer').classList.add('hidden');
                    document.getElementById('gruposVariedadesList').innerHTML = '';
                    gruposVariedadesCounter = 0;
                }
            } catch (error) {
                console.error('Error al cargar variedades:', error);
            }
        }

        // Close modal when clicking overlay
        document.getElementById('productModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });

        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const modal = document.getElementById('productModal');
                if (modal.classList.contains('show')) {
                    closeModal();
                }
            }
        });
    </script>

    </div>
</div>

    <style>
    /* Animación simple de carga */
    .loading {
        animation: spin 1s linear infinite;
    }
    
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
    
    /* Modal overlay */
    .modal-overlay {
        transition: opacity 0.2s ease;
        opacity: 0;
        visibility: hidden;
    }
    
    .modal-overlay.show {
        opacity: 1;
        visibility: visible;
    }
    
    .modal-content {
        transition: transform 0.2s ease;
        transform: scale(0.95);
    }
    
    .modal-overlay.show .modal-content {
        transform: scale(1);
    }
    
    /* File upload área */
    .file-upload-area.dragover {
        border-color: #3b82f6;
        background-color: rgba(59, 130, 246, 0.1);
    }
    
    /* Custom Scrollbar */
    .custom-scrollbar::-webkit-scrollbar {
        width: 8px;
    }
    
    .custom-scrollbar::-webkit-scrollbar-track {
        background: rgba(30, 41, 59, 0.5);
        border-radius: 4px;
    }
    
    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: rgba(59, 130, 246, 0.5);
        border-radius: 4px;
    }
    
    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background: rgba(59, 130, 246, 0.7);
    }
    
    /* Line clamp utilities */
    .truncate {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    
    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    
    /* Remove spinner from number inputs */
    input[type="number"]::-webkit-inner-spin-button,
    input[type="number"]::-webkit-outer-spin-button {
        opacity: 1;
    }
    </style>