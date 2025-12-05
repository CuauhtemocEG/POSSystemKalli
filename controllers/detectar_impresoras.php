<?php
header('Content-Type: application/json');

/**
 * 🖨️ DETECCIÓN MULTIPLATAFORMA DE IMPRESORAS TÉRMICAS
 * Compatible con Windows y macOS
 */

try {
    $impresoras = [];
    $os = strtolower(PHP_OS);
    $osNombre = 'Desconocido';
    
    // ═══════════════════════════════════════════════════════════
    // 🍎 DETECCIÓN PARA macOS
    // ═══════════════════════════════════════════════════════════
    if (strpos($os, 'darwin') !== false) {
        $osNombre = 'macOS';
        
        // Método 1: lpstat (impresoras configuradas en el sistema)
        $output = shell_exec('lpstat -v 2>/dev/null');
        if ($output) {
            $lines = explode("\n", $output);
            foreach ($lines as $line) {
                if (preg_match('/(?:device for|dispositivo para) (.+?): (.+)/', $line, $matches)) {
                    $nombre = trim($matches[1]);
                    $dispositivo = trim($matches[2]);
                    
                    if (!empty($nombre)) {
                        $puerto = 'Sistema';
                        if (stripos($dispositivo, 'usb') !== false) {
                            $puerto = 'USB';
                        } elseif (stripos($dispositivo, 'dnssd') !== false || stripos($dispositivo, 'ipp') !== false) {
                            $puerto = 'WiFi/Red';
                        }
                        
                        $tipo = 'Impresora';
                        if (stripos($nombre . ' ' . $dispositivo, 'gprinter') !== false ||
                            stripos($nombre . ' ' . $dispositivo, 'thermal') !== false ||
                            stripos($nombre . ' ' . $dispositivo, 'receipt') !== false ||
                            stripos($nombre . ' ' . $dispositivo, 'pos') !== false ||
                            stripos($nombre . ' ' . $dispositivo, 'tm-') !== false ||
                            stripos($nombre . ' ' . $dispositivo, 'tsp') !== false) {
                            $tipo = 'Térmica';
                        }
                        
                        $impresoras[] = [
                            'nombre' => $nombre,
                            'tipo' => $tipo,
                            'estado' => 'Detectada',
                            'puerto' => $puerto,
                            'sistema' => 'macOS'
                        ];
                    }
                }
            }
        }
        
        // Método 2: USB directo
        $usbOutput = shell_exec('system_profiler SPUSBDataType 2>/dev/null');
        if ($usbOutput) {
            if (preg_match_all('/([^:\n]+Printer[^:\n]*):.*?Manufacturer: ([^\n]+)/is', $usbOutput, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $nombreUSB = trim($match[1]);
                    $fabricante = trim($match[2]);
                    
                    if (!empty($nombreUSB) && !empty($fabricante)) {
                        // Verificar si ya existe
                        $existe = false;
                        foreach ($impresoras as $imp) {
                            if (stripos($imp['nombre'], $nombreUSB) !== false || 
                                stripos($nombreUSB, $imp['nombre']) !== false) {
                                $existe = true;
                                break;
                            }
                        }
                        
                        if (!$existe) {
                            $impresoras[] = [
                                'nombre' => $fabricante . ' ' . $nombreUSB,
                                'tipo' => 'USB Térmica',
                                'estado' => 'USB Conectada',
                                'puerto' => 'USB',
                                'sistema' => 'macOS'
                            ];
                        }
                    }
                }
            }
        }
    } 
    // ═══════════════════════════════════════════════════════════
    // 🪟 DETECCIÓN PARA WINDOWS
    // ═══════════════════════════════════════════════════════════
    elseif (strpos($os, 'win') !== false) {
        $osNombre = 'Windows';

        // Método 1: PowerShell - Impresoras instaladas (PRIORITARIO)
        $command = 'powershell -Command "Get-Printer | Select-Object Name, PortName, DriverName | ConvertTo-Csv -NoTypeInformation" 2>nul';
        $output = shell_exec($command);

        if ($output) {
            $lines = explode("\n", trim($output));
            // Saltar encabezados
            for ($i = 1; $i < count($lines); $i++) {
                $line = trim($lines[$i]);
                if (empty($line)) continue;

                $campos = str_getcsv($line);
                if (count($campos) >= 3) {
                    $nombre = trim($campos[0], '"');
                    $puerto = trim($campos[1], '"');
                    $driver = trim($campos[2], '"');

                    if (!empty($nombre)) {
                        $tipoPuerto = 'Sistema';
                        if (stripos($puerto, 'USB') !== false) {
                            $tipoPuerto = 'USB';
                        } elseif (stripos($puerto, 'COM') !== false || stripos($puerto, 'LPT') !== false) {
                            $tipoPuerto = 'Serial';
                        } elseif (preg_match('/\d+\.\d+\.\d+\.\d+/', $puerto)) {
                            $tipoPuerto = 'Red/IP';
                        }

                        $tipo = 'Impresora';
                        $buscarTermica = strtolower($nombre . ' ' . $driver . ' ' . $puerto);
                        if (stripos($buscarTermica, 'thermal') !== false ||
                            stripos($buscarTermica, 'receipt') !== false ||
                            stripos($buscarTermica, 'pos') !== false ||
                            stripos($buscarTermica, 'tm-') !== false ||
                            stripos($buscarTermica, 'epson') !== false ||
                            stripos($buscarTermica, 'star') !== false ||
                            stripos($buscarTermica, 'citizen') !== false ||
                            stripos($buscarTermica, 'bixolon') !== false ||
                            stripos($buscarTermica, 'xprinter') !== false ||
                            stripos($buscarTermica, '80mm') !== false ||
                            stripos($buscarTermica, '58mm') !== false) {
                            $tipo = 'Térmica';
                        }

                        $impresoras[] = [
                            'nombre' => $nombre,
                            'tipo' => $tipo,
                            'estado' => 'Detectada',
                            'puerto' => $tipoPuerto,
                            'driver' => $driver,
                            'puerto_detalle' => $puerto,
                            'sistema' => 'Windows'
                        ];
                    }
                }
            }
        }

        // Método 2: WMI (backup si PowerShell falla)
        if (empty($impresoras)) {
            $command = 'wmic printer get Name,PortName,DriverName /format:csv 2>nul';
            $output = shell_exec($command);

            if ($output) {
                $lines = explode("\n", trim($output));
                // Saltar primera línea (Node) y segunda (encabezados)
                for ($i = 2; $i < count($lines); $i++) {
                    $line = trim($lines[$i]);
                    if (empty($line)) continue;

                    $campos = str_getcsv($line);
                    if (count($campos) >= 4) {
                        // Formato: Node,DriverName,Name,PortName
                        $driverName = isset($campos[1]) ? trim($campos[1]) : '';
                        $nombre = isset($campos[2]) ? trim($campos[2]) : '';
                        $puerto = isset($campos[3]) ? trim($campos[3]) : '';

                        if (!empty($nombre)) {
                            $tipoPuerto = 'Sistema';
                            if (stripos($puerto, 'USB') !== false) {
                                $tipoPuerto = 'USB';
                            } elseif (stripos($puerto, 'COM') !== false || stripos($puerto, 'LPT') !== false) {
                                $tipoPuerto = 'Serial';
                            } elseif (preg_match('/\d+\.\d+\.\d+\.\d+/', $puerto)) {
                                $tipoPuerto = 'Red/IP';
                            }

                            $tipo = 'Impresora';
                            $buscarTermica = strtolower($nombre . ' ' . $driverName . ' ' . $puerto);
                            if (stripos($buscarTermica, 'thermal') !== false ||
                                stripos($buscarTermica, 'receipt') !== false ||
                                stripos($buscarTermica, 'pos') !== false ||
                                stripos($buscarTermica, 'tm-') !== false ||
                                stripos($buscarTermica, 'tsp') !== false ||
                                stripos($buscarTermica, 'epson') !== false ||
                                stripos($buscarTermica, 'star') !== false ||
                                stripos($buscarTermica, 'citizen') !== false ||
                                stripos($buscarTermica, 'bixolon') !== false ||
                                stripos($buscarTermica, 'xprinter') !== false ||
                                stripos($buscarTermica, '80mm') !== false ||
                                stripos($buscarTermica, '58mm') !== false) {
                                $tipo = 'Térmica';
                            }

                            $impresoras[] = [
                                'nombre' => $nombre,
                                'tipo' => $tipo,
                                'estado' => 'Detectada',
                                'puerto' => $tipoPuerto,
                                'driver' => $driverName,
                                'puerto_detalle' => $puerto,
                                'sistema' => 'Windows'
                            ];
                        }
                    }
                }
            }
        }
    }
    
    // ═══════════════════════════════════════════════════════════
    // 📋 NO AGREGAR IMPRESORAS DE PRUEBA
    // Solo mostrar impresoras realmente detectadas en el sistema
    // ═══════════════════════════════════════════════════════════
    
    echo json_encode([
        'success' => count($impresoras) > 0,
        'impresoras' => $impresoras,
        'sistema_operativo' => $osNombre,
        'sistema_php' => $os,
        'total_detectadas' => count($impresoras),
        'mensaje' => count($impresoras) > 0 
            ? count($impresoras) . ' impresora(s) detectada(s) en ' . $osNombre
            : 'No se detectaron impresoras térmicas en ' . $osNombre . '. Verifica que estén instaladas, conectadas y encendidas.'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al detectar impresoras: ' . $e->getMessage(),
        'impresoras' => [],
        'sistema_operativo' => $osNombre ?? 'Desconocido'
    ]);
}
?>
