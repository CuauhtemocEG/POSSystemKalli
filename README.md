# Mapa de arquitectura limpia para POSSystemKalli

## 1) Objetivo

Separar responsabilidades para que el proyecto sea más mantenible, testeable y seguro:
- UI / presentación
- lógica de negocio
- acceso a datos
- autenticación / seguridad
- configuración
- integración con servicios externos

---

## 2) Estado actual observado

El proyecto hoy tiene un monolito funcional con varias capas mezcladas:
- Vistas con lógica y render combinado: `views/*.php`
- Controladores con consultas SQL: `controllers/*.php`
- APIs especiales: `api/*.php`
- Auth duplicada: `auth/*.php` y `src/Auth/*.php`
- PWA/caché: `sw.js` y `js/pwa.js`
- Configuración central: `config.php`

Esto funciona, pero complica el mantenimiento, la seguridad y la evolución del sistema.

---

## 3) Arquitectura limpia propuesta

```text
POSSystemKalli/
├── .env
├── .env.example
├── composer.json
├── public/
│   ├── index.php
│   ├── login.php
│   ├── assets/
│   └── manifest.json
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── AuthController.php
│   │   │   ├── MesaController.php
│   │   │   ├── OrdenController.php
│   │   │   └── ReporteController.php
│   │   ├── Middleware/
│   │   │   ├── AuthMiddleware.php
│   │   │   └── RoleMiddleware.php
│   │   └── Requests/
│   │       ├── LoginRequest.php
│   │       └── CerrarOrdenRequest.php
│   ├── Services/
│   │   ├── AuthService.php
│   │   ├── MesaService.php
│   │   ├── OrdenService.php
│   │   └── ReporteService.php
│   ├── Repositories/
│   │   ├── UsuarioRepository.php
│   │   ├── MesaRepository.php
│   │   ├── OrdenRepository.php
│   │   └── ProductoRepository.php
│   ├── Domain/
│   │   ├── User.php
│   │   ├── Mesa.php
│   │   ├── Orden.php
│   │   └── Product.php
│   ├── Security/
│   │   ├── JwtService.php
│   │   ├── SessionManager.php
│   │   └── PermissionChecker.php
│   └── Support/
│       ├── Logger.php
│       ├── Response.php
│       └── Validator.php
├── config/
│   ├── app.php
│   ├── database.php
│   ├── cors.php
│   └── env.php
├── resources/
│   └── views/
│       ├── auth/
│       ├── mesas/
│       ├── ordenes/
│       └── reportes/
├── storage/
│   ├── logs/
│   └── cache/
├── tests/
│   ├── Unit/
│   └── Integration/
├── vendor/
├── composer.json
└── .gitignore
```

---

## 4) Regla de responsabilidad

### Controllers
Responsables de:
- recibir request
- validar entrada
- llamar al servicio correcto
- responder JSON o redirigir

No deberían:
- hacer queries de negocio directamente
- tener HTML largo
- mezclar render y lógica

### Services
Responsables de:
- reglas de negocio
- validaciones de dominio
- orquestación
- manejo de transacciones

No deberían:
- imprimir HTML
- leer directamente `$_POST` de forma masiva
- mezclar capas de presentación

### Repositories
Responsables de:
- SELECT/INSERT/UPDATE/DELETE
- SQL exclusivamente
- devolver entidades o arrays de datos

No deberían:
- decidir permisos de navegación
- controlar UI

### Views
Responsables de:
- mostrar interfaz
- bindear datos
- consumir endpoints seguros

No deberían:
- consultar DB directa
- tener lógica compleja de negocio

---

## 5) Archivos recomendados para mover / refactorizar

### A) Peligro alto: duplicación
- `controllers/newPos/` → revisar si es una segunda versión del módulo POS que compite con `views/mesa.php` y `controllers/cerrar_orden.php`
- `views/mesas.php` + `api/estado_mesas.php` + `controllers/verificar_mesa_estado.php` + `controllers/verificar_mesa_simple.php` → pueden consolidarse en una sola lógica de estado
- `auth/` + `src/Auth/` → unificar en una sola capa de autenticación

### B) Código obsoleto o legacy
- `fpdf/` → si solo se usa para tickets PDF, mantenerlo como librería externa, pero no mezclar con la lógica del negocio
- `check_tables.php` → probablemente utilitario de diagnóstico
a
- `kallijaguarposBU.sql` → útil como backup, pero no debería vivir como archivo de ejecución en producción
- `offline.html` → okay como fallback PWA, pero no mezcla con negocio

### C) Cambios recomendados de estructura
- `includes/ConfiguracionSistema.php` → mover a `app/Services/ConfiguracionSistema.php` o un `Config` repository
- `includes/EmailSender.php` → mover a `app/Services/EmailSender.php`
- `src/Auth/` → mantener solo `JWTAuth` y `AuthMiddleware` sin lógica duplicada de sesión

---

## 6) Listado práctico de archivos a revisar para eliminar o archivar

### Eliminar o mover a /archive si no se usan
- `check_tables.php`
- `kallijaguarposBU.sql` (si ya no es una base de referencia activa)
- `fpdf/` si no se usa para impresión PDF directa
- `controllers/newPos/` si es código legacy que ya no se consume
- `vendor/` no se elimina; es dependencia de Composer

### Mantener pero revisar
- `api/productController/`
- `api/promotionsController/`
- `controllers/orders/`
- `js/pwa.js`
- `sw.js`
- `includes/`

### Mantener como base de infraestructura
- `config.php`
- `conexion.php`
- `auth-check.php`
- `src/Auth/`

---

## 7) Recomendaciones de seguridad

### Obligatorio
- `.env` nunca en Git
- usar `.env.example` como plantilla
- JWT secret real en producción
- CORS restringido, no wildcard
- cookies con `HttpOnly`, `Secure`, `SameSite`
- validar permisos por backend, no solo por UI
- proteger endpoints POST/PUT/DELETE con `CSRF` o token equivalente

### Recomendado
- usar `Prepared Statements` en todas las queries
- no exponer mensajes internos de DB en producción
- usar logs estructurados
- aplicar rate limiting en login y APIs

---

## 8) Cómo empezar la migración de forma segura

### Fase 1: estabilizar
- centralizar `.env`
- unificar auth
- definir CORS
- activar caché selectivo

### Fase 2: separar
- mover `controllers/*` a `app/Http/Controllers`
- mover SQL a repositories
- mover reglas de negocio a services

### Fase 3: limpiar
- eliminar o archivar legacy
- dejar solo una versión de módulos POS
- crear tests por módulo central

---

## 9) Recomendación final

Para este proyecto, la mejor estrategia es:
- mantener una app monolítica modular
- pero con módulos separados por dominio y responsabilidades
- no más mezcla de HTML + SQL + auth + UI en cada archivo

Eso te permitirá crecer sin reescribirlo todo desde cero.
