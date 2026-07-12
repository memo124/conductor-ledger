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

- **Formato:** `pg_dump --format=plain` genera un `.sql` restaurable con `psql`, empaquetado en `.zip` (`conductorledger_YYYYMMDD_HHMMSS.zip`).
- **Generación manual:** panel **Administración → Respaldos** (permiso `admin.backups`).
- **Job mensual:** `DatabaseBackupJob` en `routes/console.php` (requiere worker de cola o `QUEUE_CONNECTION=sync`).
- **Almacenamiento:** disco `backup_local` → `storage/app/backups/YYYY/MM/`.
- **Retención:** configurable (`BACKUP_RETENTION_MONTHS`, default 12 meses).
- **Descarga:** token de un solo uso con TTL (`BackupDownloadToken`, default 15 min).
- **Notificación:** correo al administrador al generar enlace de descarga o al completar/fallar el job programado.

### Servicios (v1.2.1+)

| Servicio | Función |
|----------|---------|
| `BackupService` | Orquesta dump, ZIP, retención y tokens |
| `PgDumpResolver` | Localiza `pg_dump` (PATH, Windows, Linux, Docker) |
| `SubprocessRunner` | Ejecuta procesos con entorno mínimo en Windows |
| `ZipPackager` | Crea ZIP (`ZipArchive`, PowerShell o `zip`) |
| `PlatformPath` | Normaliza rutas (`\` / `/`) en todos los SO |

### Variables (.env)

| Variable | Descripción |
|----------|-------------|
| `PG_DUMP_BINARY` | Ruta a `pg_dump` si no está en PATH |
| `BACKUP_DISK` | Disco Laravel (default `backup_local`) |
| `BACKUP_RETENTION_MONTHS` | Meses de retención |
| `BACKUP_DOWNLOAD_TOKEN_TTL` | Minutos de validez del enlace |
| `BACKUP_NOTIFY_EMAIL` | Correo para avisos de respaldo |

### Restaurar un respaldo

```bash
# Descomprimir el ZIP y restaurar
unzip conductorledger_20260712_120000.zip
psql -h HOST -U USUARIO -d NOMBRE_BD -f conductorledger_20260712_120000.sql
```

Ver requisitos de servidor en [DEPLOYMENT.md](DEPLOYMENT.md).

## Correo (Resend)

- Plantilla formal: `resources/views/emails/formal-notification.blade.php`.
- Envío **síncrono** en la petición HTTP (registro, creación/activación de usuario, respaldos manuales).
- Notificaciones al crear usuario desde el panel admin (`UsuariosController::store`).
- Si falla el envío, la operación no se revierte; se registra en log de seguridad y se avisa en la respuesta JSON.
- El worker de cola solo es necesario para jobs en background (p. ej. `DatabaseBackupJob` programado). Ver [DEPLOYMENT.md](DEPLOYMENT.md).

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
