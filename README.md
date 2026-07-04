# ConductorLedger

Sistema web para que conductores de plataformas (InDrive, etc.) lleven el control financiero de sus viajes, gastos y vehículos. Desarrollado con **Laravel 12** y **PostgreSQL**.

## ¿Qué hace?

- Registra **viajes** con ingresos por plataforma, propinas y costo de alquiler del vehículo.
- Registra **gastos** por categoría (gasolina, mantenimiento, etc.).
- Administra **vehículos** con tipo de propiedad (propio, alquilado, financiado).
- Muestra un **dashboard** con resumen del mes y comparativa mensual.
- Genera **gráficos** anuales de ingresos, gastos y ganancia neta.
- **Exporta** viajes, gastos y resumen en CSV o PDF.
- Gestión de **usuarios** (solo administradores) con roles `admin` y `user`.

## Requisitos

| Componente | Versión mínima |
|------------|----------------|
| PHP        | 8.2            |
| PostgreSQL | 14+            |
| Composer   | 2.x            |
| Node.js    | 18+ (opcional, para Vite) |

## Instalación rápida

```bash
# 1. Clonar e instalar dependencias
composer install
cp .env.example .env
php artisan key:generate

# 2. Configurar PostgreSQL en .env
# DB_CONNECTION=pgsql
# DB_HOST=127.0.0.1
# DB_PORT=5432
# DB_DATABASE=conductor_ledger
# DB_USERNAME=postgres
# DB_PASSWORD=tu_password

# 3. Migrar y sembrar datos
php artisan migrate
php artisan db:seed

# 4. Servir la aplicación
php artisan serve
```

Accede a `http://localhost:8000` (o la URL configurada en `APP_URL`).

### Usuarios de prueba

| Rol         | Email                              | Contraseña   |
|-------------|------------------------------------|--------------|
| Conductor   | `conductor@conductorledger.local`  | `password123` |
| Administrador | `admin@conductorledger.local`    | `admin123`   |

## Estructura del proyecto

```
conductor-ledger/
├── app/
│   ├── Http/Controllers/     # Controladores (lógica de cada módulo)
│   ├── Http/Middleware/      # Middleware (ej. EnsureAdmin)
│   ├── Models/               # Modelos Eloquent
│   ├── Services/             # Lógica de negocio reutilizable
│   └── Support/              # Utilidades (Select2Response)
├── public/js/
│   ├── config/               # Rutas API centralizadas
│   ├── common/               # Utilidades JS compartidas
│   ├── auth/                 # Login, recuperación de contraseña
│   ├── viajes/               # Módulo de viajes
│   ├── gastos/               # Módulo de gastos
│   └── ...                   # Un archivo index.js por vista
├── resources/views/            # Plantillas Blade
├── routes/web.php            # Definición de rutas
└── docs/                     # Documentación detallada
```

## Stack tecnológico

**Backend:** Laravel 12, PostgreSQL (tablas particionadas por año), DomPDF para exportaciones.

**Frontend:** jQuery, Bootstrap 5, DataTables (server-side), Select2 (paginado), Chart.js (gráficos).

**Patrón:** Las vistas Blade cargan JS específico por módulo. Todas las peticiones AJAX usan `APLICATIVO_API` (`public/js/config/api_endpoints.js`) y el namespace global `ConductorLedger` para utilidades comunes.

## Documentación

| Documento | Contenido |
|-----------|-----------|
| [docs/API.md](docs/API.md) | Rutas, controladores y métodos del backend |
| [docs/JAVASCRIPT.md](docs/JAVASCRIPT.md) | Módulos JS, funciones y flujos de cada pantalla |
| [docs/ARQUITECTURA.md](docs/ARQUITECTURA.md) | Modelos, servicios, base de datos y middleware |
| [docs/RUTAS.md](docs/RUTAS.md) | Referencia rápida de todas las rutas HTTP |

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

**Vehículos ALQUILADO:** tienen cuota configurable (diaria, semanal o mensual). Al registrar un viaje, el sistema sugiere el alquiler prorrateado según el periodo.

**Numeración:** Cada usuario tiene contadores anuales independientes para viajes (`trip_number`) y gastos (`expense_number`).

## Licencia

MIT
