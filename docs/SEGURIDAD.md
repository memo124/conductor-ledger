# Seguridad

Resumen de la capa de seguridad introducida en la versión **1.0.0**.

## RBAC (control de acceso)

- **Roles:** `conductor` y `administrador` (tabla `roles`, pivot `user_role`).
- **Permisos recursivos:** árbol de opciones en `app_options`; cada rol tiene permisos CRUD por opción (`role_permissions`).
- **Middleware:** `permission:{slug}` en rutas (`AuthorizeAppOption`).
- **Servicio:** `PermissionService` con caché por usuario.

El rol legacy en `users.role` (`admin` / `user`) se mantiene por compatibilidad; se sincroniza con el rol RBAC al crear/editar usuarios.

## Cifrado de datos financieros

Modelo **envelope encryption**:

| Clave | Uso |
|-------|-----|
| **DEK** (Data Encryption Key) | Cifra viajes, gastos y resúmenes del usuario |
| **KEK** (derivada de contraseña) | Protege la DEK del usuario |
| **MASTER_ENCRYPTION_KEY** | Copia admin de la DEK para recuperación de emergencia |

- Algoritmo: AES-256-GCM.
- KDF: Argon2id (parámetros en `config/conductor-ledger.php`).
- La DEK se guarda en sesión tras login para descifrar en lectura/escritura.
- Comando de migración: `php artisan encryption:migrate-legacy`.

## Auditoría

Eventos registrados en `security_audit_logs` y log dedicado `storage/logs/security.log`:

- Registro de usuarios
- Descifrado de emergencia
- Fallos de envío de correo
- Acciones administrativas sensibles

## Respaldos

- Job mensual `DatabaseBackupJob` (pg_dump comprimido).
- Retención configurable (`BACKUP_RETENTION_MONTHS`).
- Descarga con token de un solo uso y TTL (`BackupDownloadToken`).
- Notificación por correo al administrador.

## Correo (Resend)

- Plantilla formal: `resources/views/emails/formal-notification.blade.php`.
- Cola `database`: requiere worker en producción (ver [DEPLOYMENT.md](DEPLOYMENT.md)).
- Registro no revierte si falla el correo; se registra en log de seguridad.

## Variables sensibles (.env)

| Variable | Descripción |
|----------|-------------|
| `APP_KEY` | Cifrado Laravel (sesiones, cookies) |
| `MASTER_ENCRYPTION_KEY` | Recuperación de datos cifrados |
| `RESEND_API_KEY` | API de correo |
| `DB_PASSWORD` | Acceso a PostgreSQL |

**Nunca** commitear `.env`. Rotar claves si se filtran.

## Registro de usuarios

`REGISTRATION_MODE`:

| Valor | Comportamiento |
|-------|----------------|
| `approval` | Cuenta inactiva hasta que un admin la active |
| `open` | Cuenta activa inmediatamente |

## Descifrado de emergencia

Ruta admin `/Administracion/EmergencyDecrypt` — solo administradores con permiso. Rate limit configurable (`EMERGENCY_DECRYPT_RATE_LIMIT`). Requiere `MASTER_ENCRYPTION_KEY` y deja rastro en auditoría.

## Buenas prácticas

1. Mantener `APP_DEBUG=false` en producción.
2. HTTPS obligatorio.
3. Respaldar `MASTER_ENCRYPTION_KEY` en gestor de secretos (no en el mismo servidor sin cifrar).
4. Revisar periódicamente `storage/logs/security.log`.
5. Actualizar dependencias: `composer update` con pruebas previas.
