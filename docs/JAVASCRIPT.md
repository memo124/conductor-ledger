# JavaScript — Módulos y funciones

El frontend usa jQuery y un namespace global `ConductorLedger`. Las rutas API están centralizadas en `APLICATIVO_API`.

## Orden de carga (layout `app.blade.php`)

1. jQuery, Bootstrap, DataTables, Select2 (CDN)
2. `js/common/datatables-config.js` → opciones DataTables compartidas
3. `js/config/api_endpoints.js` → define `window.APLICATIVO_API`
4. `js/common/alerts.js` → utilidades CSRF y alertas
5. `js/common/loader.js` → overlay global y spinners en botones
6. `js/common/theme.js` → tema claro/oscuro/auto
7. `js/common/logout.js` → solo si hay sesión
8. `@stack('scripts')` → JS específico de cada vista

---

## Configuración

### `public/js/config/api_endpoints.js`

Objeto `APLICATIVO_API` con todas las URLs organizadas por módulo y verbo HTTP:

```
AUTHENTICATION | PERFIL | USUARIOS | GRAFICOS | EXPORT
VIAJES | GASTOS | DASHBOARD | VEHICULOS | TIPOS_PROPIEDAD | CATEGORIAS_GASTO
```

Cada módulo expone sub-objetos `GET`, `POST`, `PUT`, `DELETE` según corresponda.

**Uso:** Siempre referenciar rutas desde aquí en lugar de strings hardcodeados.

---

## Utilidades comunes (`public/js/common/`)

### `alerts.js`

| Función | Descripción |
|---------|-------------|
| `ConductorLedger.getCsrfToken()` | Lee el token del `<meta name="csrf-token">`. |
| `ConductorLedger.setupAjaxCsrf()` | Configura `$.ajaxSetup` con header `X-CSRF-TOKEN`. Llamar al inicio de cada módulo. |
| `ConductorLedger.showAlert(message, type)` | Muestra alerta Bootstrap flotante. Tipos: `success`, `danger`, etc. Auto-cierra en 5 s. |

### `loader.js` (v1.1+)

| Función / opción AJAX | Descripción |
|-----------------------|-------------|
| `ConductorLedger.showLoader(message)` | Overlay de pantalla completa con spinner Bootstrap. |
| `ConductorLedger.hideLoader()` | Oculta el overlay. |
| `ConductorLedger.setButtonLoading($btn, true/false, text)` | Spinner en botón y deshabilita el control. |
| `ConductorLedger.setupAjaxLoader()` | Se invoca al cargar; engancha `ajaxSend` / `ajaxComplete`. |
| `clSilent: true` | Petición sin overlay (tema, sesión, Select2, DataTables). |
| `clButton` | Elemento botón para spinner local. |
| `clLoaderMessage` / `clLoadingText` | Textos personalizados de overlay y botón. |
| `data-cl-loader` | Atributo HTML en botones sueltos (ej. generar respaldo). |

Las peticiones silenciosas por URL incluyen: `Select2`, `GetDatatableServerSide`, `ActualizarSesion`, `UpdateThemePreference`, `GetRentalSuggestion`.

### `theme.js` — `ConductorLedger.Theme`

| Función / propiedad | Descripción |
|---------------------|-------------|
| `STORAGE_KEY` | `'cl_theme_preference'` en localStorage. |
| `DARK_START_HOUR` / `LIGHT_START_HOUR` | Modo auto: oscuro de 19:00 a 06:00. |
| `resolveEffective(preference)` | Convierte `auto` en `light` o `dark` según la hora. |
| `apply(theme)` | Aplica `data-theme` y `color-scheme` en `<html>`. |
| `getPreference()` | Lee preferencia del servidor (`data-user-theme`) o localStorage. |
| `setPreference(preference, options)` | Guarda localmente y opcionalmente persiste al servidor vía AJAX. |
| `init()` | Inicializa tema al cargar, enlaza `#selectThemePreference`, refresca cada 60 s en modo auto. |

### `datatables-config.js`

| Export | Descripción |
|--------|-------------|
| `ConductorLedger.defaultDataTableOptions` | Opciones base: server-side, processing con spinner, paginación 10/25/50/100, idioma español (CDN). |

**Uso:** `$.extend(true, {}, ConductorLedger.defaultDataTableOptions, { ... })`.

### `select2-paginated.js`

| Función | Descripción |
|---------|-------------|
| `ConductorLedger.initSelect2Paginated($element, options)` | Inicializa Select2 con AJAX paginado. Opciones: `url`, `placeholder`, `dropdownParent`, `extra`. |
| `ConductorLedger.validateMoneyForm($form)` | Valida que inputs `type="number"` con `min="0"` no sean negativos. Marca `.is-invalid`. |

### `logout.js`

Enlaza `#btnLogout` y `#btnLogoutSidebar` → POST logout → redirige a login.

---

## Autenticación (`public/js/auth/`)

### `login.js`

- Intercepta `#formLogin` (submit AJAX).
- Envía `email`, `password`, `remember`.
- Guarda `theme_preference` en localStorage y redirige al dashboard.

### `forgot-password.js`

- Intercepta `#formForgotPassword`.
- POST a recuperación de contraseña, muestra mensaje de éxito o error.

### `reset-password.js`

- Intercepta `#formResetPassword`.
- Envía `token`, `email`, `password`, `password_confirmation`.
- Redirige a login tras éxito.

---

## Módulos por pantalla

### `dashboard/index.js`

| Función interna | Descripción |
|-----------------|-------------|
| `cargarResumen()` | GET `/Dashboard/GetResumen` → actualiza `#statIngresos`, `#statAlquiler`, `#statGastos`, `#statNeto`. |
| `cargarComparativa()` | GET `/Viajes/GetComparativaMensual` → construye filas en `#tbodyComparativa`. |

También ejecuta keep-alive de sesión cada **5 minutos** (`ACTUALIZAR_SESION`).

### `viajes/index.js`

| Elemento / flujo | Descripción |
|------------------|-------------|
| `#selectVehicle` | Select2 paginado de vehículos. |
| `updateRentalSuggestion()` | Al cambiar vehículo o fecha, consulta `GetRentalSuggestion` y prellena/bloquea `#alquiler`. |
| `#tblViajes` | DataTable server-side con columnas de ingresos y neto. |
| `#formNuevoViaje` | Valida montos, POST store, recarga tabla y resetea modal. |

### `gastos/index.js`

| Elemento / flujo | Descripción |
|------------------|-------------|
| `#selectCategory` | Select2 de categorías. |
| `#selectVehicleGasto` | Select2 de vehículos (opcional). |
| `#tblGastos` | DataTable server-side. |
| `#formNuevoGasto` | Valida monto, POST store, recarga tabla. |

### `vehiculos/index.js`

| Elemento / flujo | Descripción |
|------------------|-------------|
| `#selectOwnership` | Select2 de tipos de propiedad (incluye `is_rented` en resultados). |
| `toggleRentalFields()` | Muestra/oculta `#rentalFields` si el tipo es ALQUILADO; exige cuota y periodo. |
| `#tblVehiculos` | DataTable server-side con botón editar. |
| `#formVehiculo` | POST store o PUT update según modo (crear/editar). |
| `#btnNuevoVehiculo` | Abre modal en modo creación. |
| `.btn-edit` | Carga fila en modal: placa, tipo, cuota, periodo, estado. |

### `graficos/index.js`

| Elemento / flujo | Descripción |
|------------------|-------------|
| `loadMetrics()` | GET `/Graficos/GetMetrics?anio=` → actualiza totales y renderiza Chart.js (línea mensual + doughnut plataformas). |
| `#selectAnio` | Dispara recarga al cambiar año. |

Destruye instancias previas de Chart antes de crear nuevas (`chartMensual`, `chartPlataformas`).

### `perfil/index.js`

| Formulario | Endpoint | Método |
|------------|----------|--------|
| `#formPerfil` | `/Perfil/Update` | PUT |
| `#formPassword` | `/Perfil/UpdatePassword` | PUT |

Al guardar perfil, sincroniza tema con `ConductorLedger.Theme.setPreference` sin re-persistir al servidor.

### `usuarios/index.js` (solo admin)

| Elemento / flujo | Descripción |
|------------------|-------------|
| `#tblUsuarios` | DataTable con botones editar/eliminar. No muestra eliminar en fila propia (`is_self`). |
| `openCreate()` | Modal en modo creación, contraseña obligatoria. |
| `.btn-edit` | Carga datos de la fila en el modal, contraseña opcional. |
| `.btn-delete` | DELETE con confirmación. |
| `#formUsuario` | POST store o PUT update según `editingId`. |

### Maestros

**`maestros/categorias-gasto.js`** y **`maestros/tipos-propiedad.js`** comparten el mismo patrón:

- DataTable en `#tblMaestro`
- Modal `#modalNuevo` para crear/editar
- `#formMaestro` → POST o PUT según `#recordId`
- Solo difieren en las constantes de `APLICATIVO_API` usadas

---

## Convenciones para nuevos módulos

1. Llamar `ConductorLedger.setupAjaxCsrf()` al inicio del `$(function() { ... })`.
2. Agregar rutas nuevas en `api_endpoints.js`.
3. Reutilizar `defaultDataTableOptions`, `initSelect2Paginated`, `validateMoneyForm` y `showAlert`.
4. Manejar errores de validación Laravel leyendo `xhr.responseJSON.errors`.
5. Tras guardar exitoso: cerrar modal Bootstrap, resetear formulario, `table.ajax.reload(null, false)`.

---

## Mapa vista → JavaScript

| Vista Blade | Archivo JS |
|-------------|------------|
| `authentication/login` | `auth/login.js` |
| `authentication/forgot-password` | `auth/forgot-password.js` |
| `authentication/reset-password` | `auth/reset-password.js` |
| `dashboard/index` | `dashboard/index.js` |
| `viajes/index` | `viajes/index.js` |
| `gastos/index` | `gastos/index.js` |
| `vehiculos/index` | `vehiculos/index.js` |
| `graficos/index` | `graficos/index.js` |
| `perfil/index` | `perfil/index.js` |
| `usuarios/index` | `usuarios/index.js` |
| `maestros/categorias-gasto/index` | `maestros/categorias-gasto.js` |
| `maestros/tipos-propiedad/index` | `maestros/tipos-propiedad.js` |
