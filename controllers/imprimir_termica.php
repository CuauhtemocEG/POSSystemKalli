<?php
require_once __DIR__ . '/../conexion.php';

$pdo = conexion();

/**
 * Clase para generar comandos ESC/POS para impresoras térmicas
 */
class ImpresorTermica {
    
    // Comandos ESC/POS básicos
    const ESC = "\x1B";
    const GS = "\x1D";
    const NUL = "\x00";
    const LF = "\x0A";
    const CR = "\x0D";
    const INIT = "\x1B\x40";           // Inicializar impresora
    const RESET = "\x1B\x40";          // Reset
    const FEED_LINE = "\x0A";          // Salto de línea
    const CUT_PAPER = "\x1D\x56\x00";  // Cortar papel
    const PARTIAL_CUT = "\x1D\x56\x01"; // Corte parcial
    
    // Alineación de texto
    const ALIGN_LEFT = "\x1B\x61\x00";
    const ALIGN_CENTER = "\x1B\x61\x01";
    const ALIGN_RIGHT = "\x1B\x61\x02";
    
    // Estilos de texto
    const TEXT_NORMAL = "\x1B\x21\x00";
    const TEXT_BOLD = "\x1B\x45\x01";
    const TEXT_BOLD_OFF = "\x1B\x45\x00";
    const TEXT_UNDERLINE = "\x1B\x2D\x01";
    const TEXT_UNDERLINE_OFF = "\x1B\x2D\x00";
    
    // Tamaños de texto
    const TEXT_SIZE_NORMAL = "\x1B\x21\x00";
    const TEXT_SIZE_WIDE = "\x1B\x21\x20";
    const TEXT_SIZE_TALL = "\x1B\x21\x10";
    const TEXT_SIZE_LARGE = "\x1B\x21\x30";
    
    private $contenido = "";
    
    public function __construct() {
        $this->contenido = self::INIT;
    }
    
    /**
     * Agregar texto con formato
     */
    public function texto($texto, $alineacion = 'left', $bold = false, $size = 'normal') {
        // Aplicar alineación
        switch ($alineacion) {
            case 'center':
                $this->contenido .= self::ALIGN_CENTER;
                break;
            case 'right':
                $this->contenido .= self::ALIGN_RIGHT;
                break;
            default:
                $this->contenido .= self::ALIGN_LEFT;
        }
        
        // Aplicar tamaño
        switch ($size) {
            case 'large':
                $this->contenido .= self::TEXT_SIZE_LARGE;
                break;
            case 'wide':
                $this->contenido .= self::TEXT_SIZE_WIDE;
                break;
            case 'tall':
                $this->contenido .= self::TEXT_SIZE_TALL;
                break;
            default:
                $this->contenido .= self::TEXT_SIZE_NORMAL;
        }
        
        // Aplicar negrita
        if ($bold) {
            $this->contenido .= self::TEXT_BOLD;
        }
        
        // Limpiar texto
        $texto = $this->limpiarTexto($texto);
        $this->contenido .= $texto;
        
        // Resetear formato
        if ($bold) {
            $this->contenido .= self::TEXT_BOLD_OFF;
        }
        
        $this->contenido .= self::LF;
    }
    
    /**
     * Agregar línea separadora
     */
    public function linea($caracter = '-', $longitud = 32) {
        $this->contenido .= self::ALIGN_LEFT;
        $this->contenido .= str_repeat($caracter, $longitud) . self::LF;
    }
    
    /**
     * Agregar imagen en formato ESC/POS
     */
    public function imagenESCPOS($rutaImagen, $anchoDeseado = 200) {
        if (!file_exists($rutaImagen)) {
            return false;
        }
        
        // Cargar imagen
        $imagen = $this->cargarImagen($rutaImagen);
        if (!$imagen) {
            return false;
        }
        
        // Obtener dimensiones originales
        list($anchoOriginal, $altoOriginal) = getimagesize($rutaImagen);
        
        // Calcular nuevo tamaño (mucho más pequeño para logo)
        $anchoFinal = min($anchoDeseado, 120); // Reducido a 120 píxeles máximo
        $altoFinal = intval(($altoOriginal * $anchoFinal) / $anchoOriginal);
        
        // Limitar altura mucho más (máximo 40 píxeles)
        if ($altoFinal > 20) {
            $altoFinal = 20;
            $anchoFinal = intval(($anchoOriginal * $altoFinal) / $altoOriginal);
        }
        
        // Redimensionar imagen
        $imagenRedimensionada = imagecreatetruecolor($anchoFinal, $altoFinal);
        $blanco = imagecolorallocate($imagenRedimensionada, 255, 255, 255);
        imagefill($imagenRedimensionada, 0, 0, $blanco);
        
        imagecopyresampled(
            $imagenRedimensionada, $imagen,
            0, 0, 0, 0,
            $anchoFinal, $altoFinal, $anchoOriginal, $altoOriginal
        );
        
        // Convertir a formato ESC/POS
        $comandoImagen = $this->convertirImagenESCPOS($imagenRedimensionada, $anchoFinal, $altoFinal);
        
        if ($comandoImagen) {
            $this->contenido .= self::ALIGN_CENTER;
            $this->contenido .= $comandoImagen;
        }
        
        // Limpiar memoria
        imagedestroy($imagen);
        imagedestroy($imagenRedimensionada);
        
        return true;
    }
    
    /**
     * 🔥 IMAGEN GIGANTE - Método que SÍ FUNCIONA del test_gigante.php
     * Agregar imagen extra grande optimizada para impresión
     */
    public function imagenGigante($rutaImagen = null) {
        if ($rutaImagen === null) {
            $rutaImagen = '../assets/img/LogoBlack.png';
        }
        
        if (!file_exists($rutaImagen)) {
            return false;
        }
        
        // Usar cargarImagen() que soporta PNG, JPG, GIF
        $imagenOriginal = $this->cargarImagen($rutaImagen);
        if (!$imagenOriginal) {
            return false;
        }
        
        // Obtener dimensiones
        $info = getimagesize($rutaImagen);
        $anchoOriginal = $info[0];
        $altoOriginal = $info[1];
        
        // EXACTO del test_gigante.php - Tamaño GIGANTE
        $anchoFinal = 360;
        $altoFinal = intval(($altoOriginal * $anchoFinal) / $anchoOriginal);
        
        if ($altoFinal > 180) {
            $altoFinal = 180;
            $anchoFinal = intval(($anchoOriginal * $altoFinal) / $altoOriginal);
        }
        
        // Crear imagen redimensionada con fondo blanco
        $imagenGigante = imagecreatetruecolor($anchoFinal, $altoFinal);
        $blanco = imagecolorallocate($imagenGigante, 255, 255, 255);
        imagefill($imagenGigante, 0, 0, $blanco);
        
        imagecopyresampled(
            $imagenGigante, $imagenOriginal,
            0, 0, 0, 0,
            $anchoFinal, $altoFinal, $anchoOriginal, $altoOriginal
        );
        
        // Convertir a comandos ESC/POS usando GS v 0 (EXACTO del test)
        $comandoGigante = $this->crearComandoImagenGigante($imagenGigante, $anchoFinal, $altoFinal);
        
        if ($comandoGigante) {
            $this->contenido .= self::ALIGN_CENTER;
            $this->contenido .= $comandoGigante;
            $this->contenido .= self::LF;
        }
        
        // Limpiar memoria
        imagedestroy($imagenOriginal);
        imagedestroy($imagenGigante);
        
        return true;
    }
    
    /**
     * 🚀 IMAGEN GIGANTE OPTIMIZADA - Para imágenes grandes con manejo de memoria
     */
    public function imagenGiganteOptimizada($rutaImagen = null) {
        // 🔧 OPTIMIZACIÓN: Aumentar límite de memoria temporalmente
        $memoriaOriginal = ini_get('memory_limit');
        ini_set('memory_limit', '256M');
        
        try {
            if ($rutaImagen === null) {
                $rutaImagen = '../assets/img/LogoBlack.png';
            }
            
            if (!file_exists($rutaImagen)) {
                return false;
            }
            
            // Verificar dimensiones primero sin cargar la imagen
            $info = getimagesize($rutaImagen);
            if (!$info) {
                return false;
            }
            
            $anchoOriginal = $info[0];
            $altoOriginal = $info[1];
            
            // Si la imagen es muy grande, pre-redimensionar
            $maxDimension = 2000; // Máximo 2000px en cualquier dirección
            $needsPreResize = ($anchoOriginal > $maxDimension || $altoOriginal > $maxDimension);
            
            if ($needsPreResize) {
                // Pre-redimensionar para reducir el uso de memoria
                $scaleFactor = min($maxDimension / $anchoOriginal, $maxDimension / $altoOriginal);
                $preAnchoFinal = intval($anchoOriginal * $scaleFactor);
                $preAltoFinal = intval($altoOriginal * $scaleFactor);
                
                // Cargar imagen original
                $imagenOriginal = $this->cargarImagen($rutaImagen);
                if (!$imagenOriginal) {
                    return false;
                }
                
                // Crear imagen pre-redimensionada
                $imagenPreRedim = imagecreatetruecolor($preAnchoFinal, $preAltoFinal);
                $blanco = imagecolorallocate($imagenPreRedim, 255, 255, 255);
                imagefill($imagenPreRedim, 0, 0, $blanco);
                
                imagecopyresampled(
                    $imagenPreRedim, $imagenOriginal,
                    0, 0, 0, 0,
                    $preAnchoFinal, $preAltoFinal, $anchoOriginal, $altoOriginal
                );
                
                // Liberar memoria de la imagen original INMEDIATAMENTE
                imagedestroy($imagenOriginal);
                
                // Ahora usar la imagen pre-redimensionada como "original"
                $imagenOriginal = $imagenPreRedim;
                $anchoOriginal = $preAnchoFinal;
                $altoOriginal = $preAltoFinal;
            } else {
                // Cargar imagen normalmente si no es muy grande
                $imagenOriginal = $this->cargarImagen($rutaImagen);
                if (!$imagenOriginal) {
                    return false;
                }
            }
            
            // EXACTO del test_gigante.php - Tamaño GIGANTE
            $anchoFinal = 360;
            $altoFinal = intval(($altoOriginal * $anchoFinal) / $anchoOriginal);
            
            if ($altoFinal > 180) {
                $altoFinal = 180;
                $anchoFinal = intval(($anchoOriginal * $altoFinal) / $altoOriginal);
            }
            
            // Crear imagen redimensionada con fondo blanco
            $imagenGigante = imagecreatetruecolor($anchoFinal, $altoFinal);
            $blanco = imagecolorallocate($imagenGigante, 255, 255, 255);
            imagefill($imagenGigante, 0, 0, $blanco);
            
            imagecopyresampled(
                $imagenGigante, $imagenOriginal,
                0, 0, 0, 0,
                $anchoFinal, $altoFinal, $anchoOriginal, $altoOriginal
            );
            
            // Liberar memoria de la imagen original INMEDIATAMENTE
            imagedestroy($imagenOriginal);
            
            // Convertir a comandos ESC/POS usando GS v 0 (EXACTO del test)
            $comandoGigante = $this->crearComandoImagenGigante($imagenGigante, $anchoFinal, $altoFinal);
            
            if ($comandoGigante) {
                $this->contenido .= self::ALIGN_CENTER;
                $this->contenido .= $comandoGigante;
                $this->contenido .= self::LF;
            }
            
            // Limpiar memoria
            imagedestroy($imagenGigante);
            
            return true;
            
        } finally {
            // 🔧 SIEMPRE restaurar límite de memoria original
            ini_set('memory_limit', $memoriaOriginal);
        }
    }
    
    /**
     * 🎯 IMAGEN CON CONFIGURACIÓN - Usa la configuración del sistema
     * Aplica el logo configurado en el sistema
     */
    public function imagenConfigurada() {
        global $pdo;
        
        try {
            error_log("ImpresorTermica::imagenConfigurada() - INICIANDO");
            
            // Obtener configuración del logo
            $stmt = $pdo->prepare("SELECT clave, valor FROM configuracion WHERE clave IN ('logo_activado', 'logo_imagen', 'logo_tamaño')");
            $stmt->execute();
            $configs = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
            
            $logoActivado = ($configs['logo_activado'] ?? '1') == '1';
            $logoImagen = $configs['logo_imagen'] ?? 'LogoBlack.png';
            $logoTamaño = $configs['logo_tamaño'] ?? 'grande';
            
            error_log("ImpresorTermica::imagenConfigurada() - Config: activado=$logoActivado, imagen=$logoImagen, tamaño=$logoTamaño");
            
            if (!$logoActivado) {
                error_log("ImpresorTermica::imagenConfigurada() - Logo desactivado");
                return false; // Logo desactivado
            }
            
            // 🔧 MEJORAR DETECCIÓN DE RUTAS: Probar múltiples rutas posibles
            $rutasPosibles = [
                "../assets/img/$logoImagen",           // Desde controllers/
                "assets/img/$logoImagen",              // Desde raíz POS/
                __DIR__ . "/../assets/img/$logoImagen" // Ruta absoluta
            ];
            
            $rutaImagen = null;
            foreach ($rutasPosibles as $ruta) {
                if (file_exists($ruta)) {
                    $rutaImagen = $ruta;
                    break;
                }
            }
            
            if (!$rutaImagen) {
                error_log("ImpresorTermica::imagenConfigurada() - Imagen no encontrada: $logoImagen");
                error_log("ImpresorTermica::imagenConfigurada() - Rutas probadas: " . implode(', ', $rutasPosibles));
                error_log("ImpresorTermica::imagenConfigurada() - __DIR__: " . __DIR__);
                error_log("ImpresorTermica::imagenConfigurada() - getcwd(): " . getcwd());
                return false; // Imagen no existe en ninguna ruta
            }
            
            error_log("ImpresorTermica::imagenConfigurada() - Imagen encontrada en: $rutaImagen");
            
            // 🔧 OPTIMIZACIÓN: Verificar tamaño de archivo antes de cargar
            $tamañoArchivo = filesize($rutaImagen);
            $limiteMB = 10; // Límite de 10MB
            if ($tamañoArchivo > ($limiteMB * 1024 * 1024)) {
                error_log("ImpresorTermica::imagenConfigurada() - Imagen demasiado grande: " . ($tamañoArchivo / 1024 / 1024) . "MB");
                return false;
            }
            
            // 🔧 OPTIMIZACIÓN: Verificar dimensiones sin cargar imagen completa
            $info = getimagesize($rutaImagen);
            if (!$info) {
                error_log("ImpresorTermica::imagenConfigurada() - No se pudo obtener info de imagen");
                return false;
            }
            
            error_log("ImpresorTermica::imagenConfigurada() - Dimensiones: {$info[0]}x{$info[1]}px");
            
            // Aplicar según el tamaño configurado
            switch ($logoTamaño) {
                case 'pequeño':
                    error_log("ImpresorTermica::imagenConfigurada() - Usando imagenESCPOS pequeño");
                    return $this->imagenESCPOS($rutaImagen, 120);
                case 'mediano':
                    error_log("ImpresorTermica::imagenConfigurada() - Usando imagenESCPOS mediano");
                    return $this->imagenESCPOS($rutaImagen, 240);
                case 'grande':
                default:
                    error_log("ImpresorTermica::imagenConfigurada() - Usando imagenGiganteOptimizada");
                    $resultado = $this->imagenGiganteOptimizada($rutaImagen);
                    error_log("ImpresorTermica::imagenConfigurada() - imagenGiganteOptimizada resultado: " . ($resultado ? 'SUCCESS' : 'FAILED'));
                    return $resultado;
            }
            
        } catch (Exception $e) {
            error_log("ImpresorTermica::imagenConfigurada() - EXCEPCIÓN: " . $e->getMessage());
            error_log("ImpresorTermica::imagenConfigurada() - Stack trace: " . $e->getTraceAsString());
            // En caso de error, usar logo por defecto si existe
            $rutaDefault = '../assets/img/LogoBlack.png';
            if (file_exists($rutaDefault)) {
                error_log("ImpresorTermica::imagenConfigurada() - Usando logo por defecto");
                return $this->imagenESCPOS($rutaDefault, 120); // Usar tamaño pequeño por seguridad
            }
            return false;
        }
    }
    
    /**
     * 🔥 CREAR COMANDO IMAGEN GIGANTE - EXACTO del test_gigante.php
     * Usa GS v 0 como en el test que funciona
     */
    private function crearComandoImagenGigante($imagen, $ancho, $alto) {
        $umbral = 80; // EXACTO del test
        
        // Comando GS v 0 EXACTO
        $comando = "\x1D\x76\x30" . chr(0);
        
        // Ancho en bytes
        $anchoBytes = ceil($ancho / 8);
        $comando .= chr($anchoBytes & 0xFF);
        $comando .= chr(($anchoBytes >> 8) & 0xFF);
        
        // Alto
        $comando .= chr($alto & 0xFF);
        $comando .= chr(($alto >> 8) & 0xFF);
        
        // Convertir imagen a datos bitmap EXACTO del test
        for ($y = 0; $y < $alto; $y++) {
            for ($x = 0; $x < $anchoBytes * 8; $x += 8) {
                $byte = 0;
                
                for ($bit = 0; $bit < 8; $bit++) {
                    $px = $x + $bit;
                    if ($px < $ancho) {
                        $color = imagecolorat($imagen, $px, $y);
                        $r = ($color >> 16) & 0xFF;
                        $g = ($color >> 8) & 0xFF;
                        $b = $color & 0xFF;
                        $gris = intval(0.299 * $r + 0.587 * $g + 0.114 * $b);
                        
                        if ($gris < $umbral) {
                            $byte |= (1 << (7 - $bit));
                        }
                    }
                }
                
                $comando .= chr($byte);
            }
        }
        
        return $comando;
    }
    
    /**
     * Cargar imagen desde archivo
     */
    private function cargarImagen($rutaImagen) {
        $info = getimagesize($rutaImagen);
        if (!$info) return false;
        
        switch ($info[2]) {
            case IMAGETYPE_JPEG:
                return imagecreatefromjpeg($rutaImagen);
            case IMAGETYPE_PNG:
                return imagecreatefrompng($rutaImagen);
            case IMAGETYPE_GIF:
                return imagecreatefromgif($rutaImagen);
            default:
                return false;
        }
    }
    
    /**
     * 🧪 MÉTODO DE PRUEBA - Cargar imagen públicamente para tests
     */
    public function probarCargarImagen($rutaImagen) {
        return $this->cargarImagen($rutaImagen);
    }
    
    /**
     * Convertir imagen a comandos ESC/POS bitmap
     */
    private function convertirImagenESCPOS($imagen, $ancho, $alto) {
        // Ajustar ancho a múltiplo de 8
        $anchoBytes = ceil($ancho / 8);
        $resultado = '';
        
        // Procesar imagen línea por línea
        for ($y = 0; $y < $alto; $y++) {
            // Comando ESC * para imagen de densidad normal
            $comando = self::ESC . '*' . chr(0); // Modo 0 = 8-dot single density
            
            // Ancho en bytes (little endian)
            $comando .= chr($anchoBytes & 0xFF);
            $comando .= chr(($anchoBytes >> 8) & 0xFF);
            
            // Datos de la línea
            $datosLinea = '';
            for ($byteX = 0; $byteX < $anchoBytes; $byteX++) {
                $byte = 0;
                
                // Procesar 8 píxeles por byte
                for ($bit = 0; $bit < 8; $bit++) {
                    $x = $byteX * 8 + $bit;
                    
                    if ($x < $ancho) {
                        $color = imagecolorat($imagen, $x, $y);
                        
                        // Convertir a escala de grises
                        $r = ($color >> 16) & 0xFF;
                        $g = ($color >> 8) & 0xFF;
                        $b = $color & 0xFF;
                        $gris = intval(0.299 * $r + 0.587 * $g + 0.114 * $b);
                        
                        // Si es oscuro (menos de 128), marcar el bit
                        if ($gris < 128) {
                            $byte |= (1 << (7 - $bit));
                        }
                    }
                }
                
                $datosLinea .= chr($byte);
            }
            
            $resultado .= $comando . $datosLinea . self::LF;
        }
        
        return $resultado;
    }
    
    /**
     * Agregar salto de línea
     */
    public function saltoLinea($cantidad = 1) {
        for ($i = 0; $i < $cantidad; $i++) {
            $this->contenido .= self::LF;
        }
    }
    
    /**
     * Agregar tabla de productos
     */
    public function tablaProductos($productos) {
        // Encabezado - Nueva estructura: PRODUCTO | P. UNIT | CANT | PRECIO
        $this->texto("PRODUCTO              P. UNIT  CANT    PRECIO", 'left', true);
        $this->linea('-', 45);
        
        foreach ($productos as $producto) {
            $nombre = substr($this->limpiarTexto($producto['nombre']), 0, 20);
            $precioUnitario = str_pad('$' . number_format($producto['precio'], 2), 7, ' ', STR_PAD_LEFT);
            $cantidad = str_pad($producto['cantidad'], 4, ' ', STR_PAD_LEFT);
            $precioTotal = str_pad('$' . number_format($producto['precio'] * $producto['cantidad'], 2), 10, ' ', STR_PAD_LEFT);
            
            $linea = str_pad($nombre, 22) . $precioUnitario . ' ' . $cantidad . ' ' . $precioTotal;
            $this->contenido .= self::ALIGN_LEFT . $linea . self::LF;
        }
        
        $this->linea('-', 45);
    }
    
    /**
     * Cortar papel
     */
    public function cortar($parcial = false) {
        $this->saltoLinea(3);
        if ($parcial) {
            $this->contenido .= self::PARTIAL_CUT;
        } else {
            $this->contenido .= self::CUT_PAPER;
        }
    }
    
    /**
     * Convertir número a texto en español para tickets
     */
    public function numeroATexto($numero) {
        $pesos = intval($numero);
        $centavos = intval(($numero - $pesos) * 100);
        
        $unidades = ["", "UNO", "DOS", "TRES", "CUATRO", "CINCO", "SEIS", "SIETE", "OCHO", "NUEVE"];
        $decenas = ["", "", "VEINTE", "TREINTA", "CUARENTA", "CINCUENTA", "SESENTA", "SETENTA", "OCHENTA", "NOVENTA"];
        $centenas = ["", "CIENTO", "DOSCIENTOS", "TRESCIENTOS", "CUATROCIENTOS", "QUINIENTOS", 
                    "SEISCIENTOS", "SETECIENTOS", "OCHOCIENTOS", "NOVECIENTOS"];
        
        // Función auxiliar para convertir números menores a 1000
        $convertir999 = function($num) use ($unidades, $decenas, $centenas) {
            if ($num == 0) return "";
            
            $resultado = "";
            
            // Centenas
            if ($num >= 100) {
                $c = intval($num / 100);
                if ($num == 100) {
                    $resultado = "CIEN";
                } else {
                    $resultado = $centenas[$c];
                }
                $num %= 100;
                if ($num > 0) $resultado .= " ";
            }
            
            // Decenas especiales (10-19, 20-29)
            if ($num >= 10 && $num <= 29) {
                $especiales = [
                    10 => "DIEZ", 11 => "ONCE", 12 => "DOCE", 13 => "TRECE", 14 => "CATORCE", 15 => "QUINCE",
                    16 => "DIECISEIS", 17 => "DIECISIETE", 18 => "DIECIOCHO", 19 => "DIECINUEVE",
                    20 => "VEINTE", 21 => "VEINTIUNO", 22 => "VEINTIDOS", 23 => "VEINTITRES", 24 => "VEINTICUATRO",
                    25 => "VEINTICINCO", 26 => "VEINTISEIS", 27 => "VEINTISIETE", 28 => "VEINTIOCHO", 29 => "VEINTINUEVE"
                ];
                $resultado .= $especiales[$num];
            } elseif ($num >= 30) {
                // Decenas normales (30-99)
                $d = intval($num / 10);
                $u = $num % 10;
                $resultado .= $decenas[$d];
                if ($u > 0) {
                    $resultado .= " Y " . $unidades[$u];
                }
            } elseif ($num > 0) {
                // Solo unidades (1-9)
                $resultado .= $unidades[$num];
            }
            
            return $resultado;
        };
        
        // Convertir pesos
        $textoPesos = "";
        
        if ($pesos == 0) {
            $textoPesos = "CERO";
        } elseif ($pesos < 1000) {
            $textoPesos = $convertir999($pesos);
        } elseif ($pesos < 1000000) {
            // Miles
            $miles = intval($pesos / 1000);
            $resto = $pesos % 1000;
            
            if ($miles == 1) {
                $textoPesos = "MIL";
            } else {
                $textoPesos = $convertir999($miles) . " MIL";
            }
            
            if ($resto > 0) {
                $textoPesos .= " " . $convertir999($resto);
            }
        }
        
        // Formatear resultado final
        if ($pesos == 1) {
            $resultado = "UN PESO";
        } else {
            $resultado = $textoPesos . " PESOS";
        }
        
        // Agregar centavos
        if ($centavos > 0) {
            if ($centavos == 1) {
                $resultado .= " CON UN CENTAVO";
            } else {
                $textoCentavos = $convertir999($centavos);
                $resultado .= " CON " . $textoCentavos . " CENTAVOS";
            }
        }
        
        return $resultado . " 00/100 M.N.";
    }

    /**
     * Dividir texto largo en líneas apropiadas para tickets térmicos
     */
    public function dividirTextoParaTicket($texto, $anchoMaximo = 32) {
        // Si el texto es corto, devolverlo como está
        if (strlen($texto) <= $anchoMaximo) {
            return [$texto];
        }
        
        $palabras = explode(' ', $texto);
        $lineas = [];
        $lineaActual = '';
        
        foreach ($palabras as $palabra) {
            $pruebaLinea = $lineaActual . ($lineaActual ? ' ' : '') . $palabra;
            
            if (strlen($pruebaLinea) <= $anchoMaximo) {
                $lineaActual = $pruebaLinea;
            } else {
                // Si la línea actual no está vacía, agregarla
                if ($lineaActual) {
                    $lineas[] = $lineaActual;
                }
                $lineaActual = $palabra;
            }
        }
        
        // Agregar la última línea
        if ($lineaActual) {
            $lineas[] = $lineaActual;
        }
        
        return $lineas;
    }

    /**
     * Formatear nombre del método de pago para impresión
     */
    public function formatearMetodoPago($metodo) {
        $metodos = [
            'efectivo' => 'EFECTIVO',
            'debito' => 'TARJETA DE DÉBITO',
            'credito' => 'TARJETA DE CRÉDITO',
            'transferencia' => 'TRANSFERENCIA BANCARIA'
        ];
        
        return $metodos[$metodo] ?? strtoupper($metodo);
    }

    /**
     * Limpiar texto para impresora térmica
     */
    private function limpiarTexto($texto) {
        // Convertir caracteres especiales
        $caracteres = array(
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
            'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U',
            'ñ' => 'n', 'Ñ' => 'N', 'ü' => 'u', 'Ü' => 'U',
            'ç' => 'c', 'Ç' => 'C'
        );
        
        $texto = strtr($texto, $caracteres);
        
        // Mantener solo caracteres ASCII imprimibles
        $texto = preg_replace('/[^\x20-\x7E]/', '', $texto);
        
        return $texto;
    }
    
    /**
     * Obtener contenido para enviar a impresora
     */
    public function obtenerComandos() {
        return $this->contenido;
    }
    
    /**
     * Agregar comando personalizado ESC/POS
     */
    public function agregarComando($comando) {
        $this->contenido .= $comando;
    }
    
    /**
     * 🖨️ ENVIAR A IMPRESORA - MULTIPLATAFORMA (Windows + macOS)
     * Detecta automáticamente el sistema operativo y usa el método apropiado
     */
    public function imprimir($nombreImpresora) {
        // Crear archivo temporal
        $archivoTemp = tempnam('/tmp', 'kalli_ticket_');
        if ($archivoTemp === false || file_put_contents($archivoTemp, $this->contenido) === false) {
            return [
                'success' => false,
                'mensaje' => 'No se pudo crear el archivo temporal de impresión',
                'salida' => 'El proceso PHP no tiene permisos para escribir en /tmp',
                'sistema' => PHP_OS_FAMILY,
                'impresora' => $nombreImpresora
            ];
        }
        
        $resultado = '';
        $success = false;
        
        try {
            // ═══════════════════════════════════════════════════════════
            // 🪟 WINDOWS - Usar método nativo de Windows
            // ═══════════════════════════════════════════════════════════
            // PHP_OS_FAMILY es más confiable que strpos($os,'win'): 'darwin' contiene 'win' como subcadena
            if (PHP_OS_FAMILY === 'Windows') {
                // Método 1: copy - Más simple y directo para impresoras compartidas/USB
                $nombreImpresoraEscapado = str_replace('"', '""', $nombreImpresora);
                $archivoEscapado = str_replace('/', '\\', $archivoTemp);
                
                // Intentar con copy (funciona con impresoras compartidas y locales)
                $comando = 'copy /B "' . $archivoEscapado . '" "\\\\localhost\\' . $nombreImpresoraEscapado . '" 2>&1';
                $resultado = shell_exec($comando);
                
                // Si copy falló, intentar con print
                if (stripos($resultado, 'error') !== false || stripos($resultado, 'no se puede') !== false) {
                    $comando = 'print /D:"' . $nombreImpresoraEscapado . '" "' . $archivoEscapado . '" 2>&1';
                    $resultado = shell_exec($comando);
                }
                
                $success = (stripos($resultado, 'error') === false && stripos($resultado, 'no se puede') === false);
                
            } 
            // ═══════════════════════════════════════════════════════════
            // 🍎 macOS - Usar CUPS (lpr)
            // ═══════════════════════════════════════════════════════════
            elseif (PHP_OS_FAMILY === 'Darwin') {
                // -o raw evita que CUPS filtre los bytes ESC/POS con el driver asignado (p.ej. Epson dot-matrix)
                $comando = "lpr -P " . escapeshellarg($nombreImpresora) . " -o raw " . escapeshellarg($archivoTemp) . " 2>&1";
                $resultado = shell_exec($comando);
                
                // lpr no devuelve salida si tiene éxito
                $success = (empty($resultado) || stripos($resultado, 'error') === false);
                
            } 
            // ═══════════════════════════════════════════════════════════
            // 🐧 LINUX - Usar CUPS (lpr)
            // ═══════════════════════════════════════════════════════════
            else {
                $comando = "lpr -P " . escapeshellarg($nombreImpresora) . " -o raw " . escapeshellarg($archivoTemp) . " 2>&1";
                $resultado = shell_exec($comando);
                
                $success = (empty($resultado) || stripos($resultado, 'error') === false);
            }
            
        } catch (Exception $e) {
            $resultado = 'Error: ' . $e->getMessage();
            $success = false;
        } finally {
            // Limpiar archivo temporal
            if (file_exists($archivoTemp)) {
                unlink($archivoTemp);
            }
        }
        
        return [
            'success' => $success,
            'mensaje' => $success ? 'Impresión enviada correctamente' : 'Error al imprimir',
            'salida' => $resultado,
            'sistema' => PHP_OS_FAMILY,
            'impresora' => $nombreImpresora
        ];
    }
    
    /**
     * 🖨️ IMPRIMIR DIRECTO - Para casos donde se necesita envío inmediato
     * Retorna solo el resultado del comando (backward compatibility)
     */
    public function imprimirDirecto($nombreImpresora) {
        $resultado = $this->imprimir($nombreImpresora);
        return $resultado['salida'] ?? '';
    }
}

// Si se llama directamente, procesar impresión
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['tipo'])) {
            throw new Exception('Tipo de impresión no especificado');
        }
        
        $impresora = new ImpresorTermica();
        
        switch ($input['tipo']) {
            case 'prueba':
                // Generar ticket de prueba
                // ✅ USAR IMAGEN CONFIGURADA
                $impresora->imagenConfigurada();
                $impresora->texto('KALLI JAGUAR', 'center', true, 'large');
                
                // Obtener y mostrar dirección de la empresa
                $stmt_dir = $pdo->prepare("SELECT valor FROM configuracion WHERE clave = 'empresa_direccion'");
                $stmt_dir->execute();
                $direccion = $stmt_dir->fetch();
                
                if ($direccion && !empty($direccion['valor'])) {
                    // Dividir la dirección en líneas para mejor presentación
                    $lineasDireccion = $impresora->dividirTextoParaTicket($direccion['valor'], 32);
                    foreach ($lineasDireccion as $linea) {
                        $impresora->texto($linea, 'center');
                    }
                }
                
                $impresora->saltoLinea();
                
                $impresora->texto('PRUEBA IMPRESORA TERMICA', 'center', true, 'large');
                $impresora->saltoLinea();
                $impresora->texto('Fecha: ' . date('d/m/Y H:i:s'), 'left');
                $impresora->texto('Sistema POS - Test', 'left');
                $impresora->saltoLinea();
                $impresora->linea('=', 32);
                $impresora->saltoLinea();
                $impresora->texto('MENSAJE DE PRUEBA', 'center', true);
                $impresora->saltoLinea();
                $impresora->texto('Si puede leer este mensaje,', 'center');
                $impresora->texto('la impresora termica esta', 'center');
                $impresora->texto('configurada correctamente.', 'center');
                $impresora->saltoLinea();
                $impresora->linea('=', 32);
                $impresora->saltoLinea();
                $impresora->texto('PRUEBA DE CARACTERES', 'center', true);
                $impresora->saltoLinea();
                $impresora->texto('Español: aeiou n', 'left');
                $impresora->texto('Numeros: 1234567890', 'left');
                $impresora->texto('Simbolos: $ @ # % & *', 'left');
                $impresora->saltoLinea();
                $impresora->linea('=', 32);
                $impresora->texto('Fin del test', 'center');
                $impresora->cortar();
                
                // Si se especifica impresora, imprimir
                if (isset($input['impresora'])) {
                    $resultadoImpresion = $impresora->imprimir($input['impresora']);
                    echo json_encode([
                        'success' => $resultadoImpresion['success'],
                        'message' => $resultadoImpresion['mensaje'],
                        'sistema' => $resultadoImpresion['sistema'],
                        'salida' => $resultadoImpresion['salida']
                    ]);
                } else {
                    // Solo devolver comandos
                    echo json_encode([
                        'success' => true,
                        'comandos' => base64_encode($impresora->obtenerComandos()),
                        'message' => 'Comandos ESC/POS generados'
                    ]);
                }
                break;
                
            case 'prueba_imagen':
                // Prueba específica solo para la imagen
                $impresora->texto('=== PRUEBA DE IMAGEN GIGANTE ===', 'center', true);
                $impresora->saltoLinea();
                $impresora->texto('Tamaño: 360 píxeles de ancho', 'center');
                $impresora->saltoLinea();
                $impresora->linea('-', 32);
                $impresora->saltoLinea();
                
                // ✅ IMAGEN GIGANTE que SÍ funciona
                $impresora->imagenGigante('../assets/img/LogoBlack.png');
                $impresora->texto('Kalli Jaguar', 'center', true, 'large');
                $impresora->saltoLinea();
                
                $impresora->linea('-', 32);
                $impresora->texto('Fin de prueba imagen', 'center');
                $impresora->cortar();
                
                // Si se especifica impresora, imprimir
                if (isset($input['impresora'])) {
                    $resultadoImpresion = $impresora->imprimir($input['impresora']);
                    echo json_encode([
                        'success' => $resultadoImpresion['success'],
                        'message' => $resultadoImpresion['mensaje'],
                        'sistema' => $resultadoImpresion['sistema'],
                        'salida' => $resultadoImpresion['salida']
                    ]);
                } else {
                    // Solo devolver comandos
                    echo json_encode([
                        'success' => true,
                        'comandos' => base64_encode($impresora->obtenerComandos()),
                        'message' => 'Comandos ESC/POS generados para prueba de imagen'
                    ]);
                }
                break;
                
            case 'prueba_logo':
                // Prueba específica para el logo configurado
                $logoImagen = $input['logo_imagen'] ?? 'LogoBlack.png';
                $logoTamaño = $input['logo_tamaño'] ?? 'grande';
                $logoActivado = $input['logo_activado'] ?? true;
                
                $impresora->texto('=== PRUEBA DE LOGO CONFIGURADO ===', 'center', true);
                $impresora->saltoLinea();
                $impresora->texto("Imagen: $logoImagen", 'center');
                $impresora->texto("Tamaño: $logoTamaño", 'center');
                $impresora->saltoLinea();
                $impresora->linea('-', 32);
                $impresora->saltoLinea();
                
                // Usar imagen con tamaño configurado
                if ($logoActivado && $logoImagen) {
                    $rutaImagen = "../assets/img/$logoImagen";
                    
                    // Ajustar método según el tamaño - USAR VERSIONES OPTIMIZADAS
                    switch ($logoTamaño) {
                        case 'pequeño':
                            $impresora->imagenESCPOS($rutaImagen, 120);
                            break;
                        case 'mediano':
                            $impresora->imagenESCPOS($rutaImagen, 240);
                            break;
                        case 'grande':
                        default:
                            $impresora->imagenGiganteOptimizada($rutaImagen);
                            break;
                    }
                } else {
                    $impresora->texto('[Logo desactivado]', 'center');
                }
                
                $impresora->texto('Kalli Jaguar', 'center', true, 'large');
                
                // Obtener y mostrar dirección de la empresa
                $stmt_dir = $pdo->prepare("SELECT valor FROM configuracion WHERE clave = 'empresa_direccion'");
                $stmt_dir->execute();
                $direccion_config = $stmt_dir->fetch();
                
                if ($direccion_config && !empty($direccion_config['valor'])) {
                    $lineasDireccion = $impresora->dividirTextoParaTicket($direccion_config['valor'], 32);
                    foreach ($lineasDireccion as $linea) {
                        $impresora->texto($linea, 'center');
                    }
                }
                
                $impresora->saltoLinea();
                $impresora->linea('-', 32);
                $impresora->texto('Configuración aplicada correctamente', 'center');
                $impresora->texto('Fecha: ' . date('d/m/Y H:i:s'), 'center');
                $impresora->cortar();
                
                // Si se especifica impresora, imprimir
                if (isset($input['impresora'])) {
                    $resultadoImpresion = $impresora->imprimir($input['impresora']);
                    echo json_encode([
                        'success' => $resultadoImpresion['success'],
                        'message' => $resultadoImpresion['mensaje'],
                        'sistema' => $resultadoImpresion['sistema'],
                        'salida' => $resultadoImpresion['salida']
                    ]);
                } else {
                    echo json_encode([
                        'success' => true,
                        'comandos' => base64_encode($impresora->obtenerComandos()),
                        'message' => 'Comandos ESC/POS generados para prueba de logo'
                    ]);
                }
                break;

            case 'cierre_rapido':
                $fechaDesde = $input['fecha_desde'] ?? date('Y-m-d');
                $fechaHasta = $input['fecha_hasta'] ?? $fechaDesde;
                $ventaTotal = floatval($input['venta_total'] ?? 0);
                $ventaTarjeta = floatval($input['venta_tarjeta'] ?? 0);
                $transferencia = floatval($input['transferencia'] ?? 0);
                $gastos = is_array($input['gastos'] ?? null) ? $input['gastos'] : [];
                $totalGastos = floatval($input['total_gastos'] ?? 0);
                $propina = floatval($input['propina'] ?? 0);
                $efectivo = floatval($input['efectivo'] ?? ($ventaTotal - $ventaTarjeta - $transferencia - $propina - $totalGastos));
                $productosPorTipo = is_array($input['productos_por_tipo'] ?? null) ? $input['productos_por_tipo'] : [];
                $productosCanceladosPorTipo = is_array($input['productos_cancelados_por_tipo'] ?? null) ? $input['productos_cancelados_por_tipo'] : [];

                // Impresora: misma configuración base usada por cerrar_orden.php
                $nombreImpresora = $input['impresora'] ?? null;
                if (!$nombreImpresora) {
                    $stmtConfigImp = $pdo->prepare("SELECT clave, valor FROM configuracion WHERE clave IN ('impresion_automatica', 'nombre_impresora')");
                    $stmtConfigImp->execute();
                    $configImp = $stmtConfigImp->fetchAll(PDO::FETCH_KEY_PAIR);
                    $nombreImpresora = $configImp['nombre_impresora'] ?? '';
                }

                if (empty($nombreImpresora)) {
                    throw new Exception('No hay impresora térmica configurada (nombre_impresora)');
                }

                // Encabezado del ticket
                $impresora->imagenConfigurada();

                $stmtEmp = $pdo->prepare("SELECT clave, valor FROM configuracion WHERE clave IN ('empresa_nombre', 'empresa_direccion')");
                $stmtEmp->execute();
                $configEmp = $stmtEmp->fetchAll(PDO::FETCH_KEY_PAIR);

                $empresaNombre = $configEmp['empresa_nombre'] ?? 'KALLI JAGUAR';
                $empresaDireccion = $configEmp['empresa_direccion'] ?? '';

                if (!empty($empresaDireccion)) {
                    $lineasDireccion = $impresora->dividirTextoParaTicket($empresaDireccion, 32);
                    foreach ($lineasDireccion as $linea) {
                        $impresora->texto($linea, 'center');
                    }
                    $impresora->saltoLinea();
                }

                $impresora->texto($empresaNombre, 'center', true);
                $impresora->texto('Corte del día', 'center', true, 'large');
                $impresora->linea('=', 24);
                $impresora->texto('Periodo: ' . date('d/m/Y', strtotime($fechaDesde)) . ' al ' . date('d/m/Y', strtotime($fechaHasta)), 'left');
                $impresora->texto('Impreso: ' . date('d/m/Y H:i:s'), 'left');
                $impresora->linea('-', 45);

                $impresora->texto('Venta total:      $' . number_format($ventaTotal, 2), 'left');
                $impresora->texto('Venta tarjeta:    $' . number_format($ventaTarjeta, 2), 'left');
                $impresora->texto('Propina:          $' . number_format($propina, 2), 'left');
                $impresora->texto('Total Terminal:   $' . number_format($ventaTarjeta + $propina, 2), 'left');
                $impresora->texto('Transferencia:    $' . number_format($transferencia, 2), 'left');

                $impresora->linea('-', 45);
                $impresora->texto('GASTOS', 'left', true);

                if (empty($gastos)) {
                    $impresora->texto('Sin gastos registrados', 'left');
                } else {
                    foreach ($gastos as $gasto) {
                        $concepto = trim((string)($gasto['concepto'] ?? 'Gasto'));
                        $monto = floatval($gasto['monto'] ?? 0);
                        if ($concepto === '') {
                            $concepto = 'Gasto';
                        }
                        $conceptoCorto = substr($concepto, 0, 26);
                        $montoTxt = '$' . number_format($monto, 2);
                        $espacios = 45 - strlen($conceptoCorto) - strlen($montoTxt);
                        $linea = $conceptoCorto . str_repeat('.', max(1, $espacios)) . $montoTxt;
                        $impresora->texto($linea, 'left');
                    }
                }

                $impresora->linea('-', 45);
                $impresora->texto('Total gastos:     $' . number_format($totalGastos, 2), 'left', true);
                $impresora->linea('=', 45);
                $impresora->texto('EFECTIVO: $' . number_format($efectivo, 2), 'right', true, 'large');

                // Productos vendidos por tipo
                if (!empty($productosPorTipo)) {
                    $impresora->saltoLinea();
                    $impresora->linea('=', 45);
                    $impresora->texto('PRODUCTOS VENDIDOS POR TIPO', 'center', true);
                    $impresora->linea('=', 45);

                    foreach ($productosPorTipo as $tipoNombre => $productos) {
                        $tipoStr = strtoupper((string)$tipoNombre);
                        $totalTipo = array_sum(array_column($productos, 'cantidad'));
                        $impresora->saltoLinea();
                        // Encabezado del tipo con total
                        $totalTipoTxt = '(' . $totalTipo . ' pzas)';
                        $espaciosTipo = 45 - strlen($tipoStr) - strlen($totalTipoTxt);
                        $impresora->texto($tipoStr . str_repeat(' ', max(1, $espaciosTipo)) . $totalTipoTxt, 'left', true);
                        $impresora->linea('-', 45);

                        foreach ($productos as $prod) {
                            $nombre = trim((string)($prod['nombre'] ?? ''));
                            $cantidad = (int)($prod['cantidad'] ?? 0);
                            if ($nombre === '' || $cantidad <= 0) {
                                continue;
                            }
                            $nombreCorto = substr($nombre, 0, 37);
                            $cantTxt = (string)$cantidad;
                            $espaciosProd = 45 - strlen($nombreCorto) - strlen($cantTxt);
                            $impresora->texto($nombreCorto . str_repeat('.', max(1, $espaciosProd)) . $cantTxt, 'left');
                        }
                    }
                    $impresora->linea('=', 45);
                }

                $impresora->saltoLinea();
                $impresora->linea('-', 45);
                $impresora->texto('FIN CIERRE RAPIDO', 'center');

                                // Productos cancelados por tipo
                                if (!empty($productosCanceladosPorTipo)) {
                                    $impresora->saltoLinea();
                                    $impresora->linea('=', 45);
                                    $impresora->texto('CANCELADOS', 'center', true);
                                    $impresora->linea('=', 45);

                                    foreach ($productosCanceladosPorTipo as $tipoNombre => $productos) {
                                        $tipoStr = strtoupper((string)$tipoNombre);
                                        $totalTipo = array_sum(array_column($productos, 'cantidad'));
                                        $impresora->saltoLinea();
                                        $totalTipoTxt = '(' . $totalTipo . ' pzas)';
                                        $espaciosTipo = 45 - strlen($tipoStr) - strlen($totalTipoTxt);
                                        $impresora->texto($tipoStr . str_repeat(' ', max(1, $espaciosTipo)) . $totalTipoTxt, 'left', true);
                                        $impresora->linea('-', 45);

                                        foreach ($productos as $prod) {
                                            $nombre = trim((string)($prod['nombre'] ?? ''));
                                            $cantidad = (int)($prod['cantidad'] ?? 0);
                                            if ($nombre === '' || $cantidad <= 0) {
                                                continue;
                                            }
                                            $nombreCorto = substr($nombre, 0, 37);
                                            $cantTxt = (string)$cantidad;
                                            $espaciosProd = 45 - strlen($nombreCorto) - strlen($cantTxt);
                                            $impresora->texto($nombreCorto . str_repeat('.', max(1, $espaciosProd)) . $cantTxt, 'left');
                                        }
                                    }
                                    $impresora->linea('=', 45);
                                }
                $impresora->saltoLinea();
                $impresora->cortar();

                $resultadoImpresion = $impresora->imprimir($nombreImpresora);
                echo json_encode([
                    'success' => $resultadoImpresion['success'],
                    'message' => $resultadoImpresion['mensaje'],
                    'sistema' => $resultadoImpresion['sistema'] ?? null,
                    'salida' => $resultadoImpresion['salida'] ?? null
                ]);
                break;
                
            case 'ticket':
                // Generar ticket de orden
                if (!isset($input['orden_id'])) {
                    throw new Exception('ID de orden no especificado');
                }
                
                // Obtener datos de la orden CON DESCUENTO MANUAL Y ES_PERSONAL
                $stmt = $pdo->prepare("
                    SELECT o.*, m.nombre as mesa_nombre,
                           COALESCE(o.aplicar_descuento_porcentaje, 0) as aplicar_descuento_porcentaje,
                           COALESCE(o.descuento_porcentaje_valor, 0) as descuento_porcentaje_valor,
                           COALESCE(o.es_personal, 0) as es_personal,
                           COALESCE(m.aplicar_promociones, 1) as aplicar_promociones,
                           COALESCE(m.es_para_llevar, 0) as es_para_llevar
                    FROM ordenes o 
                    JOIN mesas m ON o.mesa_id=m.id 
                    WHERE o.id = ?
                ");
                $stmt->execute([$input['orden_id']]);
                $orden = $stmt->fetch();
                
                if (!$orden) {
                    throw new Exception('Orden no encontrada');
                }
                
                // 💰 Extraer datos del descuento manual y es_personal
                $desc_data = [
                    'aplicar_descuento_porcentaje' => $orden['aplicar_descuento_porcentaje'],
                    'descuento_porcentaje_valor' => $orden['descuento_porcentaje_valor']
                ];
                $es_personal = (bool)$orden['es_personal'];
                $aplicar_promociones_mesa = (bool)$orden['aplicar_promociones'] && !$orden['es_para_llevar'];
                
                // Obtener productos de la orden AGRUPADOS por producto
                // Suma todas las cantidades del mismo producto (confirmado=1) menos las canceladas
                $stmt = $pdo->prepare("
                    SELECT 
                        p.id,
                        p.nombre, 
                        p.precio,
                        SUM(op.cantidad - COALESCE(op.cancelado, 0)) as cantidad
                    FROM orden_productos op 
                    JOIN productos p ON op.producto_id = p.id 
                    WHERE op.orden_id = ?
                      AND COALESCE(op.confirmado, 1) = 1
                    GROUP BY p.id, p.nombre, p.precio
                    HAVING SUM(op.cantidad - COALESCE(op.cancelado, 0)) > 0
                    ORDER BY p.nombre
                ");
                $stmt->execute([$input['orden_id']]);
                $productos = $stmt->fetchAll();
                
                // Generar ticket
                // ✅ USAR IMAGEN CONFIGURADA
                $impresora->imagenConfigurada();

                // Obtener datos de la sucursal y dirección
                $stmt = $pdo->prepare("SELECT clave, valor FROM configuracion WHERE clave IN ('empresa_nombre', 'empresa_direccion')");
                $stmt->execute();
                $configuraciones = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
                
                $empresaDireccion = $configuraciones['empresa_direccion'] ?? '';

                // Mostrar dirección si está configurada
                if (!empty($empresaDireccion)) {
                    $lineasDireccion = $impresora->dividirTextoParaTicket($empresaDireccion, 32);
                    foreach ($lineasDireccion as $linea) {
                        $impresora->texto($linea, 'center');
                    }
                }
                $impresora->saltoLinea();

                $impresora->texto('Sucursal: ' . $configuraciones['empresa_nombre'], 'left');
                $impresora->texto('Mesa: ' . $orden['mesa_nombre'], 'left');
                $impresora->texto('Orden: #' . $orden['codigo'], 'left');
                $impresora->texto('Fecha: ' . date('d/m/Y H:i:s', strtotime($orden['creada_en'])), 'left');
                $impresora->saltoLinea();
                $impresora->linea('=', 45);
                $impresora->saltoLinea();
                
                // Productos
                $impresora->tablaProductos($productos);
                
                // Calcular subtotal antes de descuentos
                $subtotalCalculado = 0;
                foreach ($productos as $producto) {
                    $subtotalCalculado += $producto['cantidad'] * $producto['precio'];
                }
                
                $impresora->saltoLinea();
                $impresora->texto('Subtotal: $' . number_format($subtotalCalculado, 2), 'right');
                
                // 🎁 Obtener promociones correctamente según el estado de la orden
                $promociones = [];
                $total_descuentos_promociones = 0;
                
                try {
                    // Si la orden está cerrada, usar las promociones guardadas en la BD
                    if ($orden['estado'] === 'cerrada') {
                        $stmtPromosGuardadas = $pdo->prepare("
                            SELECT pa.*, p.nombre, p.tipo
                            FROM promociones_aplicadas pa
                            JOIN promociones p ON pa.promocion_id = p.id
                            WHERE pa.orden_id = ?
                        ");
                        $stmtPromosGuardadas->execute([$input['orden_id']]);
                        $promos_guardadas = $stmtPromosGuardadas->fetchAll(PDO::FETCH_ASSOC);
                        
                        foreach ($promos_guardadas as $promo) {
                            $promociones[] = [
                                'nombre' => $promo['nombre'],
                                'tipo' => $promo['tipo'],
                                'monto' => floatval($promo['descuento_aplicado']),
                                'detalle' => ''
                            ];
                            $total_descuentos_promociones += floatval($promo['descuento_aplicado']);
                        }
                    } else {
                        // Si la orden está abierta, calcular promociones SOLO si la mesa tiene promociones activadas
                        if (!$aplicar_promociones_mesa) {
                            // No calcular promociones si están desactivadas en la mesa
                            error_log("Promociones desactivadas para mesa en orden {$input['orden_id']}");
                        } else {
                            // Obtener productos SIN agrupar para cálculo correcto
                            $stmtProductosPromo = $pdo->prepare("
                            SELECT 
                                op.id as orden_producto_id,
                                op.producto_id,
                                p.nombre,
                                p.precio,
                                p.categoria,
                                (op.cantidad - COALESCE(op.cancelado, 0)) as cantidad
                            FROM orden_productos op
                            JOIN productos p ON op.producto_id = p.id
                            WHERE op.orden_id = ? 
                            AND COALESCE(op.confirmado, 1) = 1
                            AND (op.cantidad - COALESCE(op.cancelado, 0)) > 0
                        ");
                        $stmtProductosPromo->execute([$input['orden_id']]);
                        $productos_promo = $stmtProductosPromo->fetchAll(PDO::FETCH_ASSOC);
                        
                        // Obtener promociones activas
                        $stmtPromo = $pdo->query("
                            SELECT p.*
                            FROM promociones p
                            WHERE p.activa = 1
                            AND (p.fecha_inicio IS NULL OR p.fecha_inicio <= NOW())
                            AND (p.fecha_fin IS NULL OR p.fecha_fin >= NOW())
                            ORDER BY p.prioridad DESC, p.id DESC
                        ");
                        $promociones_activas = $stmtPromo->fetchAll(PDO::FETCH_ASSOC);
                        
                        $productos_usados = [];
                        
                        foreach ($promociones_activas as $promo) {
                            // ⚠️ Saltar promociones de descuento personal si no está activado
                            if ($promo['tipo'] === 'descuento_personal' && !$es_personal) {
                                continue;
                            }
                            
                            $productos_elegibles = [];
                            
                            // Filtrar productos elegibles según el tipo de promoción
                            if ($promo['aplica_a'] === 'productos') {
                                $stmtProdPromo = $pdo->prepare("SELECT producto_id FROM promocion_productos WHERE promocion_id = ?");
                                $stmtProdPromo->execute([$promo['id']]);
                                $ids_permitidos = $stmtProdPromo->fetchAll(PDO::FETCH_COLUMN);
                                
                                foreach ($productos_promo as $prod) {
                                    if (in_array($prod['producto_id'], $ids_permitidos) && !in_array($prod['orden_producto_id'], $productos_usados)) {
                                        $productos_elegibles[] = $prod;
                                    }
                                }
                            } elseif ($promo['aplica_a'] === 'categorias') {
                                $stmtCatPromo = $pdo->prepare("SELECT categoria FROM promocion_categorias WHERE promocion_id = ?");
                                $stmtCatPromo->execute([$promo['id']]);
                                $cats_permitidas = $stmtCatPromo->fetchAll(PDO::FETCH_COLUMN);
                                
                                foreach ($productos_promo as $prod) {
                                    if (in_array($prod['categoria'], $cats_permitidas) && !in_array($prod['orden_producto_id'], $productos_usados)) {
                                        $productos_elegibles[] = $prod;
                                    }
                                }
                            } else {
                                foreach ($productos_promo as $prod) {
                                    if (!in_array($prod['orden_producto_id'], $productos_usados)) {
                                        $productos_elegibles[] = $prod;
                                    }
                                }
                            }
                            
                            if (count($productos_elegibles) < intval($promo['minimo_productos'])) {
                                continue;
                            }
                            
                            $monto_descuento = 0;
                            $productos_afectados = [];
                            
                            // Calcular descuento según tipo de promoción
                            switch ($promo['tipo']) {
                                case '2x1':
                                    // Expandir productos por cantidad y ordenar por precio ascendente
                                    $items_expandidos = [];
                                    foreach ($productos_elegibles as $prod) {
                                        for ($i = 0; $i < $prod['cantidad']; $i++) {
                                            $items_expandidos[] = $prod;
                                        }
                                    }
                                    
                                    // Ordenar por precio ascendente (más barato primero)
                                    usort($items_expandidos, function($a, $b) {
                                        return $a['precio'] <=> $b['precio'];
                                    });
                                    
                                    $total_items = count($items_expandidos);
                                    $pares = floor($total_items / 2);
                                    
                                    // Aplicar descuento: descontar los N más baratos
                                    for ($par = 0; $par < $pares; $par++) {
                                        $item_gratis = $items_expandidos[$par];
                                        $monto_descuento += $item_gratis['precio'];
                                        $productos_afectados[] = $item_gratis['orden_producto_id'];
                                    }
                                    break;
                                    
                                case '3x2':
                                    // Expandir productos por cantidad y ordenar por precio ascendente
                                    $items_expandidos = [];
                                    foreach ($productos_elegibles as $prod) {
                                        for ($i = 0; $i < $prod['cantidad']; $i++) {
                                            $items_expandidos[] = $prod;
                                        }
                                    }
                                    
                                    // Ordenar por precio ascendente (más barato primero)
                                    usort($items_expandidos, function($a, $b) {
                                        return $a['precio'] <=> $b['precio'];
                                    });
                                    
                                    $total_items = count($items_expandidos);
                                    $grupos = floor($total_items / 3);
                                    
                                    // Aplicar descuento: descontar los N más baratos
                                    for ($grupo = 0; $grupo < $grupos; $grupo++) {
                                        $item_gratis = $items_expandidos[$grupo];
                                        $monto_descuento += $item_gratis['precio'];
                                        $productos_afectados[] = $item_gratis['orden_producto_id'];
                                    }
                                    break;
                                    
                                case 'descuento_porcentaje':
                                    $porcentaje = floatval($promo['valor']) / 100;
                                    foreach ($productos_elegibles as $prod) {
                                        $monto_descuento += ($prod['precio'] * $prod['cantidad']) * $porcentaje;
                                        $productos_afectados[] = $prod['orden_producto_id'];
                                    }
                                    break;
                                    
                                case 'descuento_personal':
                                    // Descuento personal: aplica porcentaje a todos los productos elegibles
                                    $porcentaje = floatval($promo['valor']) / 100;
                                    foreach ($productos_elegibles as $prod) {
                                        $monto_descuento += ($prod['precio'] * $prod['cantidad']) * $porcentaje;
                                        $productos_afectados[] = $prod['orden_producto_id'];
                                    }
                                    break;
                                    
                                case 'descuento_fijo':
                                    $monto_descuento = floatval($promo['valor']);
                                    foreach ($productos_elegibles as $prod) {
                                        $productos_afectados[] = $prod['orden_producto_id'];
                                    }
                                    break;
                            }
                            
                            if ($monto_descuento > 0) {
                                $promociones[] = [
                                    'nombre' => $promo['nombre'],
                                    'tipo' => $promo['tipo'],
                                    'monto' => round($monto_descuento, 2),
                                    'detalle' => ''
                                ];
                                $total_descuentos_promociones += $monto_descuento;
                                $productos_usados = array_merge($productos_usados, array_unique($productos_afectados));
                            }
                        }
                        } // Fin del if aplicar_promociones_mesa
                    }
                } catch (Exception $e) {
                    error_log("Error calculando promociones en ticket: " . $e->getMessage());
                }
                
                // Mostrar promociones en el ticket
                if (count($promociones) > 0) {
                    $impresora->saltoLinea();
                    $impresora->linea('-', 45);
                    $impresora->texto('PROMOCIONES APLICADAS:', 'left', true);
                    
                    foreach ($promociones as $promo) {
                        // Limitar nombre a 25 caracteres
                        $nombrePromo = substr($promo['nombre'], 0, 25);
                        $montoPromo = '-$' . number_format($promo['monto'], 2);
                        
                        // Formatear línea: NOMBRE................-$XXX.XX
                        $espacios = 45 - strlen($nombrePromo) - strlen($montoPromo);
                        $linea = $nombrePromo . str_repeat('.', max(1, $espacios)) . $montoPromo;
                        $impresora->texto($linea, 'left');
                    }
                    
                    $impresora->saltoLinea();
                    $impresora->texto('Total Descuentos: -$' . number_format($total_descuentos_promociones, 2), 'right', true);
                }
                
                // 💰 Calcular descuento manual por porcentaje
                $descuento_porcentaje_aplicado = 0;
                if ($desc_data['aplicar_descuento_porcentaje'] == 1 && $desc_data['descuento_porcentaje_valor'] > 0) {
                    $base_descuento = $subtotalCalculado - $total_descuentos_promociones;
                    $descuento_porcentaje_aplicado = round($base_descuento * ($desc_data['descuento_porcentaje_valor'] / 100), 2);
                }
                
                // 💰 Mostrar descuento porcentaje manual si está aplicado
                if ($descuento_porcentaje_aplicado > 0) {
                    $impresora->saltoLinea();
                    $impresora->linea('-', 45);
                    $impresora->texto('DESCUENTO MANUAL APLICADO:', 'left', true);
                    
                    $porcentaje_str = number_format($desc_data['descuento_porcentaje_valor'], 1) . '%';
                    $monto_desc_str = '-$' . number_format($descuento_porcentaje_aplicado, 2);
                    
                    // Formatear línea: Descuento XX%...........-$XXX.XX
                    $espacios = 45 - strlen('Descuento ' . $porcentaje_str) - strlen($monto_desc_str);
                    $linea = 'Descuento ' . $porcentaje_str . str_repeat('.', max(1, $espacios)) . $monto_desc_str;
                    $impresora->texto($linea, 'left');
                    
                    $impresora->saltoLinea();
                    $impresora->texto('Descuento Manual: -$' . number_format($descuento_porcentaje_aplicado, 2), 'right', true);
                }
                
                // Calcular total con promociones Y descuento manual
                $totalCalculado = $subtotalCalculado - $total_descuentos_promociones - $descuento_porcentaje_aplicado;
                
                // Total
                $impresora->saltoLinea();
                $impresora->linea('=', 45);
                $impresora->texto('TOTAL: $' . number_format($totalCalculado, 2), 'right', true, 'wide');
                $impresora->saltoLinea();
                
                // Total en texto (optimizado para tickets térmicos)
                $totalTexto = $impresora->numeroATexto($totalCalculado);
                
                // Dividir texto largo en líneas para mejor ajuste
                $lineas = $impresora->dividirTextoParaTicket($totalTexto, 32);
                foreach ($lineas as $linea) {
                    $impresora->texto($linea, 'center', false, 'normal');
                }
                $impresora->saltoLinea();
                
                // Obtener productos cancelados (si los hay)
                // Agrupa por producto y suma las cantidades canceladas
                $stmt = $pdo->prepare("
                    SELECT p.nombre, p.precio, SUM(op.cancelado) as cantidad
                    FROM orden_productos op 
                    JOIN productos p ON op.producto_id = p.id 
                    WHERE op.orden_id = ? AND op.cancelado > 0
                    GROUP BY op.producto_id, p.nombre, p.precio
                ");
                $stmt->execute([$input['orden_id']]);
                $productosCancelados = $stmt->fetchAll();
                
                // Mostrar productos cancelados si existen
                if (!empty($productosCancelados)) {
                    $impresora->linea('-', 45);
                    $impresora->texto('PRODUCTOS CANCELADOS:', 'left', true);
                    $impresora->saltoLinea();
                    
                    // Usar el mismo formato nuevo: PRODUCTO | P. UNIT | CANT | PRECIO
                    foreach ($productosCancelados as $producto) {
                        $nombre = substr($producto['nombre'], 0, 20);
                        $precioUnitario = str_pad('$' . number_format($producto['precio'], 2), 7, ' ', STR_PAD_LEFT);
                        $cantidad = str_pad($producto['cantidad'], 4, ' ', STR_PAD_LEFT);
                        $precioTotal = str_pad('$' . number_format($producto['precio'] * $producto['cantidad'], 2), 10, ' ', STR_PAD_LEFT);
                        
                        $linea = str_pad($nombre, 22) . $precioUnitario . ' ' . $cantidad . ' ' . $precioTotal;
                        $impresora->texto($linea, 'left');
                    }
                    $impresora->saltoLinea();
                }
                
                $impresora->linea('=', 45);
                $impresora->texto('Gracias por su compra!', 'center');
                $impresora->saltoLinea();
                $impresora->cortar();
                
                // Imprimir si se especifica impresora
                if (isset($input['impresora'])) {
                    $resultadoImpresion = $impresora->imprimir($input['impresora']);
                    echo json_encode([
                        'success' => $resultadoImpresion['success'],
                        'message' => $resultadoImpresion['mensaje'],
                        'sistema' => $resultadoImpresion['sistema'],
                        'salida' => $resultadoImpresion['salida']
                    ]);
                } else {
                    echo json_encode([
                        'success' => true,
                        'comandos' => base64_encode($impresora->obtenerComandos()),
                        'message' => 'Comandos ESC/POS generados'
                    ]);
                }
                break;
                
            default:
                throw new Exception('Tipo de impresión no válido');
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}
?>
