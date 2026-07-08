# ConductorLedger

![Versión](https://img.shields.io/badge/versión-1.0.0-blue)
![Laravel](https://img.shields.io/badge/Laravel-12-red)
![PHP](https://img.shields.io/badge/PHP-8.2+-777)

Sistema web para que conductores de plataformas (InDrive, etc.) lleven el control financiero de sus viajes, gastos y vehículos. Desarrollado con **Laravel 12** y **PostgreSQL**.

## ¿Qué hace?

- Registra **viajes** con ingresos por plataforma, propinas y costo de alquiler del vehículo.
- Registra **gastos** por categoría (gasolina, mantenimiento, etc.).
- Administra **vehículos** con tipo de propiedad (propio, alquilado, financiado).
- Muestra un **dashboard** con resumen del mes y comparativa mensual.
- Genera **gráficos** anuales de ingresos, gastos y ganancia neta.
- **Exporta** viajes, gastos y resumen en CSV o PDF.
- **Registro público** de conductores con aprobación por administrador.
- **RBAC** con permisos granulares por módulo del menú.
- **Cifrado** de datos financieros sensibles por usuario.
- **Respaldos** automáticos de base de datos y notificaciones por correo (Resend).
- Gestión de **usuarios** (administradores) con activación, roles y auditoría.

## Versión actual

| Versión | Fecha | Notas |
|---------|-------|-------|
| **1.0.0** | 2026-07-08 | Seguridad, RBAC, cifrado, backups, correo, i18n ES |

Historial completo: [CHANGELOG.md](CHANGELOG.md) · Política de versiones: [docs/VERSIONAMIENTO.md](docs/VERSIONAMIENTO.md)

```bash
cat VERSION
php artisan about
```

## Requisitos

| Componente | Versión mínima |
|------------|----------------|
| PHP        | 8.2 (+ `pdo_pgsql`, `openssl`, `sodium`) |
| PostgreSQL | 14+            |
| Composer   | 2.x            |
| Node.js    | 18+ (opcional, para Vite) |

## Instalación rápida (desarrollo)

```bash
# 1. Clonar e instalar dependencias
composer install
cp .env.example .env
php artisan key:generate

# 2. Clave maestra de cifrado (guardar en lugar seguro)
php -r "echo 'MASTER_ENCRYPTION_KEY=base64:'.base64_encode(random_bytes(32)).PHP_EOL;"

# 3. Configurar PostgreSQL, Resend y demás variables en .env
# DB_CONNECTION=pgsql
# DB_HOST=127.0.0.1
# DB_PORT=5432
# DB_DATABASE=conductor_ledger
# DB_USERNAME=postgres
# DB_PASSWORD=tu_password
# RESEND_API_KEY=re_...
# MAIL_FROM_ADDRESS=onboarding@resend.dev   # solo desarrollo

# 4. Migrar y sembrar datos
php artisan migrate
php artisan db:seed

# 5. Servir la aplicación (solo desarrollo local)
php artisan serve

# 6. Worker de cola (correos, jobs) — terminal aparte
php artisan queue:work
```

Accede a `http://127.0.0.1:8000` (ajusta `APP_URL` en `.env`).

> **Producción:** no uses `artisan serve`. Usa Apache/Nginx apuntando a `public/`. Ver [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md).

### Usuarios de prueba

| Rol           | Email                             | Contraseña    |
|---------------|-----------------------------------|---------------|
| Conductor     | `conductor@conductorledger.local` | `password123` |
| Administrador | `admin@conductorledger.local`     | `admin123`    |

## Comandos útiles

| Comando | Descripción |
|---------|-------------|
| `php artisan migrate` | Ejecutar migraciones |
| `php artisan db:seed` | Datos iniciales (roles, permisos, usuarios demo) |
| `php artisan queue:work` | Procesar cola (correos, respaldos) |
| `php artisan schedule:run` | Tareas programadas (usar vía cron en producción) |
| `php artisan encryption:migrate-legacy` | Migrar registros antiguos al esquema cifrado |
| `php artisan test` | Ejecutar pruebas |
| `php artisan config:clear` | Limpiar caché tras cambiar `.env` |

## Estructura del proyecto

```
conductor-ledger/
├── app/
│   ├── Console/Commands/     # Comandos Artisan
│   ├── DTO/                  # Objetos de transferencia (notificaciones)
│   ├── Http/Controllers/   # Controladores por módulo
│   ├── Http/Middleware/      # RBAC, admin, permisos
│   ├── Jobs/                 # Jobs en cola (respaldos)
│   ├── Mail/                 # Correos formales (Resend)
│   ├── Models/               # Eloquent + RBAC
│   └── Services/             # Cifrado, permisos, backups, auditoría
├── config/
│   └── conductor-ledger.php  # Versión, cifrado, backups, registro
├── lang/es/                  # Validación y mensajes en español
├── public/js/
│   ├── config/               # Rutas API (APLICATIVO_API)
│   ├── common/               # DataTables, sidebar, alertas, tema
│   └── {modulo}/index.js     # Un JS por pantalla
├── resources/views/          # Blade + emails
├── routes/web.php            # Rutas HTTP con middleware permission
├── docs/                     # Documentación detallada
├── VERSION                   # Versión SemVer actual
└── CHANGELOG.md              # Historial de cambios
```

## Stack tecnológico

**Backend:** Laravel 12, PostgreSQL (tablas particionadas por año), DomPDF, Resend, Argon2id + AES-256-GCM.

**Frontend:** jQuery, Bootstrap 5, DataTables (server-side + tarjetas móvil), Select2, Chart.js.

**Patrón:** Vistas Blade + JS por módulo. AJAX centralizado en `APLICATIVO_API` y utilidades en `ConductorLedger`.

## Documentación

| Documento | Contenido |
|-----------|-----------|
| [docs/API.md](docs/API.md) | Rutas, controladores y métodos del backend |
| [docs/JAVASCRIPT.md](docs/JAVASCRIPT.md) | Módulos JS, funciones y flujos de cada pantalla |
| [docs/ARQUITECTURA.md](docs/ARQUITECTURA.md) | Modelos, servicios, base de datos y middleware |
| [docs/RUTAS.md](docs/RUTAS.md) | Referencia rápida de todas las rutas HTTP |
| [docs/SEGURIDAD.md](docs/SEGURIDAD.md) | RBAC, cifrado, auditoría, respaldos y correo |
| [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md) | Apache, Nginx, cola, cron y producción |
| [docs/VERSIONAMIENTO.md](docs/VERSIONAMIENTO.md) | SemVer, tags y flujo de releases |
| [CHANGELOG.md](CHANGELOG.md) | Historial de versiones |

## Seguridad (v1.0.0)

- Permisos por opción de menú (`permission:slug` en rutas).
- Cifrado envelope de viajes/gastos por usuario.
- Registro con modo aprobación (`REGISTRATION_MODE=approval`).
- Auditoría en `storage/logs/security.log`.
- Respaldos mensuales con descarga tokenizada.

Detalle: [docs/SEGURIDAD.md](docs/SEGURIDAD.md)

## Variables de entorno importantes

| Variable | Descripción |
|----------|-------------|
| `APP_KEY` | Clave Laravel |
| `MASTER_ENCRYPTION_KEY` | Recuperación de datos cifrados |
| `REGISTRATION_MODE` | `approval` o `open` |
| `QUEUE_CONNECTION` | `database` (producción) o `sync` (simple) |
| `RESEND_API_KEY` | API de correo |
| `SECURITY_ADMIN_EMAIL` | Notificaciones admin |
| `APP_LOCALE` | `es` (mensajes en español) |

Ver [.env.example](.env.example) completo.

## Conceptos clave del negocio

**Ganancia neta de un viaje:**

```
Ingresos = InDrive + Otros viajes + Propina
Neto     = Ingresos − Alquiler
```

**Ganancia neta del mes (dashboard):**

```
Neto = Ingresos totales − Alquiler total − Gastos totales
```

**Vehículos ALQUILADO:** cuota configurable (diaria, semanal o mensual). Al registrar un viaje, el sistema sugiere el alquiler prorrateado.

**Numeración:** contadores anuales independientes por usuario para viajes (`trip_number`) y gastos (`expense_number`).

## Desarrollo local vs producción

| Aspecto | Desarrollo | Producción |
|---------|------------|------------|
| Servidor HTTP | `php artisan serve` | Apache/Nginx → `public/` |
| Cola | `php artisan queue:work` | Supervisor, cron o `sync` |
| Tareas programadas | Manual / cron | Cron `schedule:run` cada minuto |
| Correo Resend | `onboarding@resend.dev` | Dominio verificado |
| Debug | `APP_DEBUG=true` | `APP_DEBUG=false` |

Guía completa: [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md)

## Contribuir y versionar

1. Desarrollar en rama feature.
2. Actualizar [CHANGELOG.md](CHANGELOG.md) bajo `[Unreleased]`.
3. Al release: bump en [VERSION](VERSION), tag `vX.Y.Z`, push con tags.

Ver [docs/VERSIONAMIENTO.md](docs/VERSIONAMIENTO.md).

## Licencia

MIT
