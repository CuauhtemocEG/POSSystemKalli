/**
 * Sistema de Impresión Térmica ESC/POS
 * Maneja la impresión directa a impresoras térmicas
 */

class ImpresorTermicaJS {
    constructor() {
        this.baseUrl = 'controllers/imprimir_termica.php';
    }

    /**
     * Imprimir ticket de prueba
     */
    async imprimirPrueba(nombreImpresora) {
        if (!nombreImpresora) {
            throw new Error('Nombre de impresora requerido');
        }

        const response = await fetch(this.baseUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                tipo: 'prueba',
                impresora: nombreImpresora
            })
        });

        return await response.json();
    }

    /**
     * Imprimir ticket de orden
     */
    async imprimirTicketOrden(ordenId, nombreImpresora) {
        if (!ordenId) {
            throw new Error('ID de orden requerido');
        }

        const response = await fetch(this.baseUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                tipo: 'ticket',
                orden_id: ordenId,
                impresora: nombreImpresora
            })
        });

        return await response.json();
    }

    /**
     * Mostrar modal de carga
     */
    mostrarCarga(mensaje = 'Imprimiendo...') {
        Swal.fire({
            title: mensaje,
            text: 'Enviando comandos ESC/POS a la impresora',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
    }

    /**
     * Mostrar resultado exitoso
     */
    mostrarExito(titulo = '¡Ticket impreso!', mensaje = 'El ticket se envió correctamente') {
        Swal.fire({
            icon: 'success',
            title: titulo,
            html: `
                <p>${mensaje}</p>
                <small class="text-slate-600">Formato: ESC/POS nativo para impresoras térmicas</small>
            `,
            confirmButtonColor: '#16a34a'
        });
    }

    /**
     * Mostrar error
     */
    mostrarError(titulo = 'Error al imprimir', mensaje = 'No se pudo enviar el ticket') {
        Swal.fire({
            icon: 'error',
            title: titulo,
            text: mensaje,
            confirmButtonColor: '#dc2626'
        });
    }
}

// Instancia global
const impresorTermica = new ImpresorTermicaJS();

/**
 * Función global para imprimir ticket térmico desde cualquier vista
 */
async function imprimirTicketTermico(ordenId, nombreImpresora = null) {
    try {
        // Si no se proporciona impresora, intentar obtenerla de la configuración
        if (!nombreImpresora) {
            // Verificar si existe la variable global (desde mesa.php)
            if (window.configImpresoraNombre) {
                nombreImpresora = window.configImpresoraNombre;
            } else {
                // Verificar si existe en el DOM (fallback)
                const configElement = document.querySelector('[data-impresora]');
                if (configElement) {
                    nombreImpresora = configElement.dataset.impresora;
                }
            }
        }

        if (!nombreImpresora) {
            impresorTermica.mostrarError(
                'Impresora no configurada',
                'Por favor, configura una impresora térmica en el panel de configuración'
            );
            return;
        }

        impresorTermica.mostrarCarga('Imprimiendo ticket térmico...');

        const resultado = await impresorTermica.imprimirTicketOrden(ordenId, nombreImpresora);

        Swal.close();

        if (resultado.success) {
            impresorTermica.mostrarExito();
        } else {
            impresorTermica.mostrarError('Error al imprimir', resultado.message || 'Error desconocido');
        }

    } catch (error) {
        Swal.close();
        impresorTermica.mostrarError('Error de conexión', 'No se pudo conectar con el servidor');
        console.error('Error imprimiendo ticket térmico:', error);
    }
}

/**
 * Función global para imprimir prueba térmica
 */
async function imprimirPruebaTermica(nombreImpresora) {
    try {
        if (!nombreImpresora) {
            impresorTermica.mostrarError(
                'Impresora requerida',
                'Por favor, especifica el nombre de la impresora'
            );
            return;
        }

        impresorTermica.mostrarCarga('Enviando prueba ESC/POS...');

        const resultado = await impresorTermica.imprimirPrueba(nombreImpresora);

        Swal.close();

        if (resultado.success) {
            impresorTermica.mostrarExito(
                '¡Prueba ESC/POS exitosa!',
                'Los comandos térmicos se enviaron correctamente'
            );
        } else {
            impresorTermica.mostrarError(
                'Error en la prueba',
                resultado.error || 'Error desconocido al enviar comandos ESC/POS'
            );
        }

    } catch (error) {
        Swal.close();
        impresorTermica.mostrarError('Error de conexión', 'No se pudo conectar con el servidor');
        console.error('Error en prueba térmica:', error);
    }
}

/**
 * Función para obtener configuración de impresora actual
 */
async function obtenerConfiguracionImpresora() {
    try {
        const response = await fetch('controllers/obtener_config_impresora.php');
        return await response.json();
    } catch (error) {
        console.error('Error obteniendo configuración de impresora:', error);
        return { success: false, error: error.message };
    }
}

// Auto-inicialización cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    console.log('🖨️ Sistema de impresión térmica ESC/POS inicializado');
    
    // Agregar data attribute para la impresora si está disponible en PHP
    if (typeof window.configImpresoraNombre !== 'undefined') {
        document.body.setAttribute('data-impresora', window.configImpresoraNombre);
    }
});
