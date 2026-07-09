# Changelog

Todos los cambios relevantes de **ConductorLedger** se documentan en este archivo.

El formato sigue [Keep a Changelog](https://keepachangelog.com/es/1.1.0/) y el proyecto usa [Versionado Semántico](https://semver.org/lang/es/).

## [Unreleased]

### Planeado
- Mejoras continuas de UX móvil y permisos granulares.

---

## [1.1.1] - 2026-07-08

Correcciones de creación de vehículos (todos los tipos de propiedad) y envío de correos al crear usuarios.

### Fixed
- **Creación de vehículos:** la validación HTML5 del navegador bloqueaba tipos distintos de **ALQUILADO** (campos de alquiler ocultos conservaban `min="0.01"` con valor `0`); ahora se deshabilitan al ocultar.
- **Correo al crear usuario:** el panel de administración no enviaba notificación al nuevo usuario; ahora envía correo formal al crear y avisa en pantalla si falla el envío.
- **Envío de correos:** las notificaciones formales se envían de forma síncrona (`Mail::send`) en lugar de encolarse, evitando falsos positivos cuando no hay worker de cola activo.

### Changed
- El worker de cola (`queue:work`) ya no es necesario para correos de notificación; sigue siendo requerido para respaldos programados (`DatabaseBackupJob`).

---

## [1.1.0] - 2026-07-08

Correcciones críticas de cifrado, loaders globales y gestión completa de vehículos alquilados.

### Added
- **Loaders globales (overlay + botón):** módulo `public/js/common/loader.js` con `ConductorLedger.showLoader()`, `hideLoader()` y `setButtonLoading()`.
- Integración automática con peticiones AJAX (delay 300 ms) e indicador «Procesando…» en DataTables.
- **Edición de vehículos:** modal unificado crear/editar con botón en tabla.
- Validación obligatoria de cuota y periodo para tipos **ALQUILADO** (cliente y servidor).
- Flag `is_rented` en Select2 de tipos de propiedad.

### Fixed
- **Cifrado de viajes/gastos:** `encrypted_payload` y `encryption_version` agregados al `$fillable` de `Trip` y `Expense` (montos ya no se guardaban en $0).
- **Sesión DEK:** `FinancialRecordService` usa `app('session.store')` en lugar de `session()` (SessionManager).
- Validación `is_active` en actualización de vehículos (valores `0`/`1`).
- Peticiones silenciosas sin loader: tema, sesión, Select2 y DataTables.

---

## [1.0.0] - 2026-07-08

Primera versión estable con módulo financiero completo y capa de seguridad empresarial.

### Added
- **RBAC recursivo:** roles `conductor` y `administrador`, permisos por opción de menú (`app_options`) y middleware `permission`.
- **Registro público** con modo `approval` o activación inmediata (`REGISTRATION_MODE`).
- **Cifrado envelope (DEK/KEK):** viajes, gastos y resúmenes mensuales cifrados por usuario con `EncryptionService`.
- **Descifrado de emergencia** para administradores con clave maestra y auditoría.
- **Respaldos automáticos** de PostgreSQL (`DatabaseBackupJob`) con descarga tokenizada para admins.
- **Notificaciones por correo** vía Resend (`FormalNotification`) para registro, activación y backups.
- **Auditoría de seguridad** en canal dedicado (`storage/logs/security.log`).
- **Interfaz responsive** con menú móvil, overlay y tablas en tarjetas en pantallas pequeñas.
- **Internacionalización:** mensajes de validación en español (`lang/es/`).
- Comando `php artisan encryption:migrate-legacy` para migrar datos históricos al esquema cifrado.
- Tests unitarios de cifrado (`EncryptionServiceTest`).

### Changed
- Controladores financieros adaptados al servicio `FinancialRecordService` con descifrado en sesión.
- Rutas protegidas con permisos granulares en lugar de solo `admin`.
- Sidebar de administración ampliado (Usuarios, Respaldos, Descifrado emergencia).

### Fixed
- Validación de `is_active` al editar usuarios (compatibilidad Laravel 12 con valores `0`/`1`).
- Envío de correos Resend en desarrollo (`onboarding@resend.dev` como remitente de prueba).

---

## [0.2.0] - 2026-07-05

### Added
- Diseño responsive para móvil: menú hamburguesa, barra superior y DataTables en modo tarjeta.

---

## [0.1.0] - 2026-07-04

### Added
- Versión inicial: viajes, gastos, vehículos, dashboard, gráficos, exportación CSV/PDF, maestros y gestión básica de usuarios.
- Documentación base en `docs/` (API, JavaScript, arquitectura, rutas).
- PostgreSQL con tablas particionadas por año.

[Unreleased]: https://github.com/compare/v1.1.1...HEAD
[1.1.1]: https://github.com/compare/v1.1.0...v1.1.1
[1.1.0]: https://github.com/compare/v1.0.0...v1.1.0
[1.0.0]: https://github.com/compare/v0.2.0...v1.0.0
[0.2.0]: https://github.com/compare/v0.1.0...v0.2.0
[0.1.0]: https://github.com/
