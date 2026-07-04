# Referencia de rutas HTTP

Prefijo base: valor de `APP_URL` (ej. `http://localhost:8000`).

Leyenda: 🔓 pública · 🔐 requiere login · 👑 requiere admin

---

## Públicas 🔓

| Método | Ruta | Nombre | Controlador |
|--------|------|--------|-------------|
| GET | `/` | — | Redirige a login |
| GET, POST | `/Authentication/Login` | `login` | AuthenticationController@login |
| GET, POST | `/Authentication/ForgotPassword` | `password.request` | AuthenticationController@forgotPassword |
| GET, POST | `/Authentication/ResetPassword/{token?}` | `password.reset` | AuthenticationController@resetPassword |

---

## Autenticadas 🔐

### Sesión y perfil

| Método | Ruta | Nombre |
|--------|------|--------|
| POST | `/Authentication/Logout` | `logout` |
| GET | `/Authentication/GetUserById` | — |
| POST | `/Authentication/UpdateThemePreference` | — |
| GET | `/Authentication/ActualizarSesion` | — |
| GET | `/Perfil` | `perfil.index` |
| PUT | `/Perfil/Update` | `perfil.update` |
| PUT | `/Perfil/UpdatePassword` | `perfil.update-password` |

### Dashboard y gráficos

| Método | Ruta | Nombre |
|--------|------|--------|
| GET | `/Dashboard` | `dashboard` |
| GET | `/Dashboard/GetResumen` | — |
| GET | `/Graficos` | `graficos.index` |
| GET | `/Graficos/GetMetrics` | — |

### Exportaciones

| Método | Ruta | Nombre | Query params |
|--------|------|--------|--------------|
| GET | `/Export/Viajes` | `export.viajes` | `anio`, `format=csv\|pdf` |
| GET | `/Export/Gastos` | `export.gastos` | `anio`, `format=csv\|pdf` |
| GET | `/Export/Resumen` | `export.resumen` | `anio`, `format=csv\|pdf` |

### Viajes

| Método | Ruta | Nombre |
|--------|------|--------|
| GET | `/Viajes` | `viajes.index` |
| GET | `/Viajes/GetDatatableServerSide` | — |
| GET | `/Viajes/GetComparativaMensual` | — |
| GET | `/Viajes/GetRentalSuggestion` | — |
| GET | `/Viajes/Select2Paginated` | — |
| POST | `/Viajes/Store` | — |

### Gastos

| Método | Ruta | Nombre |
|--------|------|--------|
| GET | `/Gastos` | `gastos.index` |
| GET | `/Gastos/GetDatatableServerSide` | — |
| GET | `/Gastos/Select2Categories` | — |
| GET | `/Gastos/Select2Vehicles` | — |
| POST | `/Gastos/Store` | — |

### Vehículos

| Método | Ruta | Nombre |
|--------|------|--------|
| GET | `/Vehiculos` | `vehiculos.index` |
| GET | `/Vehiculos/GetDatatableServerSide` | — |
| GET | `/Vehiculos/Select2Paginated` | — |
| POST | `/Vehiculos/Store` | — |
| PUT | `/Vehiculos/Update/{id}` | — |

### Maestros

| Método | Ruta | Nombre |
|--------|------|--------|
| GET | `/Maestros/TiposPropiedad` | `tipos-propiedad.index` |
| GET | `/Maestros/TiposPropiedad/GetDatatableServerSide` | — |
| POST | `/Maestros/TiposPropiedad/Store` | — |
| PUT | `/Maestros/TiposPropiedad/Update/{id}` | — |
| GET | `/Maestros/CategoriasGasto` | `categorias-gasto.index` |
| GET | `/Maestros/CategoriasGasto/GetDatatableServerSide` | — |
| POST | `/Maestros/CategoriasGasto/Store` | — |
| PUT | `/Maestros/CategoriasGasto/Update/{id}` | — |

---

## Solo administrador 👑

| Método | Ruta | Nombre |
|--------|------|--------|
| GET | `/Usuarios` | `usuarios.index` |
| GET | `/Usuarios/GetDatatableServerSide` | — |
| POST | `/Usuarios/Store` | — |
| PUT | `/Usuarios/Update/{id}` | — |
| DELETE | `/Usuarios/Delete/{id}` | — |

---

## Parámetros comunes

### DataTables (server-side)

Enviados automáticamente por DataTables en GET:

- `draw`, `start`, `length`
- `search[value]` — texto de búsqueda global

### Select2 paginado

- `q` — término de búsqueda
- `page` — número de página (15 resultados por página)

### Filtros por año

- `anio` — año calendario (default: año actual)
