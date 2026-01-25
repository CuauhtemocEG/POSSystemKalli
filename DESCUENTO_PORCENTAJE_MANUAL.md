# Sistema de Descuento Porcentaje Manual - Documentación

## Implementación Completada ✅

### 1. Base de Datos
**Archivo**: `database_updates/add_descuento_porcentaje_manual.sql`

Se agregaron dos campos a la tabla `ordenes`:
- `aplicar_descuento_porcentaje` (TINYINT): Flag para activar/desactivar el descuento
- `descuento_porcentaje_valor` (DECIMAL): Valor del porcentaje (0-100)

**Ejecutar migración**:
```sql
-- Ya ejecutado automáticamente
-- Ver: http://localhost/POS/database_updates/ejecutar_descuento_manual.php
```

### 2. Diferencias entre Promociones

#### Promoción de Personal (descuento_personal)
- **Tipo**: Promoción automática del sistema
- **Activación**: Checkbox "Descuento Personal" en mesa.php
- **Almacenamiento**: Se guarda en tabla `promociones` con `tipo = 'descuento_personal'`
- **Aplicación**: Se aplica automáticamente cuando el checkbox está marcado
- **Registro**: Se guarda en `promociones_aplicadas` al cerrar orden

#### Descuento % Manual (NEW)
- **Tipo**: Descuento manual por porcentaje
- **Activación**: Checkbox "Descuento %" + input numérico en mesa.php
- **Almacenamiento**: Se guarda directamente en campos de la tabla `ordenes`
- **Aplicación**: Se aplica manualmente ingresando el porcentaje deseado (0-100%)
- **Registro**: Los campos ya están en la orden, no requiere tabla adicional

### 3. Archivos Modificados

#### Backend
1. **controllers/actualizar_descuento_porcentaje.php** (NUEVO)
   - Endpoint AJAX para activar/desactivar descuento
   - Valida porcentaje (0-100)
   - Registra cambios en historial_ordenes

2. **controllers/newPos/orden_actual.php**
   - Línea 26-31: Obtiene campos de descuento de la orden
   - Línea 341-357: Aplica descuento porcentaje al total
   - Línea 372-377: Incluye info del descuento en JSON response

3. **controllers/cerrar_orden.php**
   - Línea 242-258: Obtiene y aplica descuento porcentaje al cerrar
   - Línea 260: Calcula total final con descuento incluido

#### Frontend
4. **views/mesa.php**
   - **Línea 525-547**: Checkbox y input de descuento porcentaje (UI)
   - **Línea 681-748**: Función `actualizarDescuentoPorcentaje()`
   - **Línea 2607-2647**: Event listeners para checkbox e input
   - **Línea 2171-2180**: Muestra descuento en sección de totales
   - **Línea 2237-2246**: Carga estado del checkbox al actualizar orden

### 4. Flujo de Uso

1. **Abrir orden en mesa**
   - Ir a: `http://localhost/POS/index.php?page=mesa&id=X`

2. **Activar descuento porcentaje**
   - Marcar checkbox "Descuento %"
   - Ingresar porcentaje (ej: 15 para 15%)
   - Presionar Enter o cambiar de campo

3. **Ver descuento aplicado**
   - El total se actualiza automáticamente
   - Aparece línea naranja con el descuento: "-$XX.XX"

4. **Cerrar orden**
   - El descuento se mantiene al cerrar
   - Se registra en la orden con subtotal y total correcto

### 5. Validaciones Implementadas

- ✅ Porcentaje debe estar entre 0-100
- ✅ Solo órdenes abiertas pueden modificarse
- ✅ Input deshabilitado si checkbox no está marcado
- ✅ Cambios se registran en historial_ordenes
- ✅ Toast notifications para feedback visual

### 6. API Response

**GET** `controllers/newPos/orden_actual.php?orden_id=X`

```json
{
  "items": [...],
  "subtotal": 1000.00,
  "total": 850.00,
  "descuento_porcentaje": {
    "aplicado": true,
    "porcentaje": 15.0,
    "monto": 150.00
  }
}
```

**POST** `controllers/actualizar_descuento_porcentaje.php`

Request:
```
orden_id: 123
aplicar: 1
porcentaje: 15
```

Response:
```json
{
  "success": true,
  "message": "Descuento de 15% activado correctamente",
  "aplicar": 1,
  "porcentaje": 15
}
```

### 7. Testing

Para probar:
1. Abrir orden de prueba
2. Agregar productos
3. Activar descuento % con 10%
4. Verificar que el total disminuye 10%
5. Cerrar orden y verificar en orden_detalle.php

### 8. Orden de Aplicación de Descuentos

```
Subtotal (productos)
  ↓
- Descuento estándar (si existe)
  ↓
- Promociones (2x1, 3x2, etc)
  ↓
- Descuento % Manual ← NUEVO
  ↓
+ Impuestos
  ↓
= TOTAL FINAL
```

### 9. Consideraciones

- El descuento % manual es **independiente** de las promociones
- Se aplica **después** de las promociones automáticas
- Es **por orden**, no por mesa (cada orden puede tener diferente %)
- Se **persiste** en la base de datos al cerrar la orden
- Visible en reportes como parte del total de la orden

---

**Fecha de implementación**: 2026-01-24
**Versión**: 1.0
