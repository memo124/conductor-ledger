# API — Controladores y métodos

Todas las rutas autenticadas requieren sesión activa (`auth` middleware). Las rutas de usuarios requieren además rol `admin` (`admin` middleware).

Las respuestas JSON siguen el patrón `{ success, message?, data? }`. Los DataTables devuelven `{ draw, recordsTotal, recordsFiltered, data }`.

---

## AuthenticationController

Gestiona login, logout, sesiones y recuperación de contraseña.

| Método | Ruta | HTTP | Descripción |
|--------|------|------|-------------|
| `login` | `/Authentication/Login` | GET | Muestra formulario de login. Redirige al dashboard si ya hay sesión. |
| `login` | `/Authentication/Login` | POST | Valida credenciales. Solo usuarios activos. Crea registro en `user_sessions`. Devuelve `redirect` y `theme_preference`. |
| `logout` | `/Authentication/Logout` | POST | Cierra sesión, invalida token CSRF y marca `logout_at` en la sesión activa. |
| `getUserById` | `/Authentication/GetUserById` | GET | Devuelve datos del usuario autenticado (`?id=` debe coincidir con el usuario logueado). |
| `updateThemePreference` | `/Authentication/UpdateThemePreference` | POST | Guarda preferencia de tema: `light`, `dark` o `auto`. |
| `actualizarSesion` | `/Authentication/ActualizarSesion` | GET | Actualiza `last_known_ip` de la sesión activa (keep-alive cada 5 min desde el dashboard). |
| `forgotPassword` | `/Authentication/ForgotPassword` | GET/POST | Envía enlace de restablecimiento vía Laravel Password broker. |
| `resetPassword` | `/Authentication/ResetPassword/{token?}` | GET/POST | Formulario y procesamiento de nueva contraseña (mín. 8 caracteres, confirmada). |

---

## DashboardController

| Método | Ruta | HTTP | Descripción |
|--------|------|------|-------------|
| `index` | `/Dashboard` | GET | Vista del dashboard. |
| `getResumen` | `/Dashboard/GetResumen` | GET | Resumen del **mes actual**: ingresos, alquiler, gastos y neto formateados. |

---

## ViajesController

Depende de `YearlyCounterService` y `VehicleRentalService`.

| Método | Ruta | HTTP | Descripción |
|--------|------|------|-------------|
| `index` | `/Viajes` | GET | Vista de listado y modal de nuevo viaje. |
| `store` | `/Viajes/Store` | POST | Crea viaje. Valida vehículo del usuario, alquiler según tipo de propiedad, asigna `trip_number` anual. |
| `getDatatableServerSide` | `/Viajes/GetDatatableServerSide` | GET | DataTable server-side con búsqueda por fecha, día o número. Calcula ingresos y neto por fila. |
| `getComparativaMensual` | `/Viajes/GetComparativaMensual` | GET | Totales mensuales por plataforma y alquiler. Parámetro: `anio`. |
| `getRentalSuggestion` | `/Viajes/GetRentalSuggestion` | GET | Metadatos del vehículo para sugerir alquiler. Parámetros: `vehicle_id`, `fecha` (opcional). |
| `select2Paginated` | `/Viajes/Select2Paginated` | GET | Select2 de vehículos activos del usuario. Parámetros: `q`, `page`. |

**Campos de `store`:** `vehicle_id`, `fecha`, `indrive`, `otros_viajes`, `propina`, `alquiler`.

---

## GastosController

Depende de `YearlyCounterService`.

| Método | Ruta | HTTP | Descripción |
|--------|------|------|-------------|
| `index` | `/Gastos` | GET | Vista de listado y modal de nuevo gasto. |
| `store` | `/Gastos/Store` | POST | Crea gasto con numeración anual. Valida que el vehículo (si se envía) pertenezca al usuario. |
| `getDatatableServerSide` | `/Gastos/GetDatatableServerSide` | GET | DataTable con join a categorías y vehículos. |
| `select2Categories` | `/Gastos/Select2Categories` | GET | Select2 de categorías de gasto. |
| `select2Vehicles` | `/Gastos/Select2Vehicles` | GET | Select2 de vehículos activos del usuario. |

**Campos de `store`:** `category_id`, `vehicle_id` (opcional), `fecha`, `monto`, `descripcion`.

---

## VehiculosController

Depende de `VehicleRentalService`.

| Método | Ruta | HTTP | Descripción |
|--------|------|------|-------------|
| `index` | `/Vehiculos` | GET | Vista de listado y modal de nuevo vehículo. |
| `getDatatableServerSide` | `/Vehiculos/GetDatatableServerSide` | GET | DataTable de vehículos del usuario. |
| `select2Paginated` | `/Vehiculos/Select2Paginated` | GET | Select2 de **tipos de propiedad** (`is_rented` en cada opción). |
| `store` | `/Vehiculos/Store` | POST | Crea vehículo. Si es ALQUILADO exige `rental_fee_daily` ≥ 0.01 y `rental_period`. |
| `update` | `/Vehiculos/Update/{id}` | PUT | Actualiza vehículo (placa, tipo, cuota, periodo, `is_active`). |

**Campos:** `ownership_type_id`, `plate_number`, `rental_fee_daily`, `rental_period` (`daily|weekly|monthly`), `is_active` (solo en update).

---

## GraficosController

| Método | Ruta | HTTP | Descripción |
|--------|------|------|-------------|
| `index` | `/Graficos` | GET | Vista con gráficos Chart.js. |
| `getMetrics` | `/Graficos/GetMetrics` | GET | Series mensuales (12 meses), totales anuales y desglose por plataforma. Parámetro: `anio`. |

---

## ExportController

| Método | Ruta | HTTP | Descripción |
|--------|------|------|-------------|
| `viajes` | `/Export/Viajes` | GET | Exporta viajes del año. Parámetros: `anio`, `format` (`csv` o `pdf`). |
| `gastos` | `/Export/Gastos` | GET | Exporta gastos del año. |
| `resumen` | `/Export/Resumen` | GET | Exporta resumen financiero (ingresos, alquiler, gastos, neto). |

Los CSV incluyen BOM UTF-8 para compatibilidad con Excel.

---

## PerfilController

| Método | Ruta | HTTP | Descripción |
|--------|------|------|-------------|
| `index` | `/Perfil` | GET | Vista de perfil del usuario autenticado. |
| `update` | `/Perfil/Update` | PUT | Actualiza nombre, email, DUI y preferencia de tema. |
| `updatePassword` | `/Perfil/UpdatePassword` | PUT | Cambia contraseña validando la actual. |

---

## UsuariosController (solo admin)

| Método | Ruta | HTTP | Descripción |
|--------|------|------|-------------|
| `index` | `/Usuarios` | GET | Vista de gestión de usuarios. |
| `getDatatableServerSide` | `/Usuarios/GetDatatableServerSide` | GET | DataTable de todos los usuarios. Marca `is_self` para el admin logueado. |
| `store` | `/Usuarios/Store` | POST | Crea usuario con rol y estado activo. Envía correo formal al nuevo usuario; avisa si falla el envío. |
| `update` | `/Usuarios/Update/{id}` | PUT | Actualiza usuario. Impide auto-desactivarse o quitarse el rol admin. Al reactivar cuenta inactiva envía correo de activación. |
| `destroy` | `/Usuarios/Delete/{id}` | DELETE | Elimina usuario. Impide auto-eliminación. |

**Campos:** `name`, `email`, `dui`, `password`, `role` (`admin|user`), `is_active`.

---

## CategoriasGastoController

| Método | Ruta | HTTP | Descripción |
|--------|------|------|-------------|
| `index` | `/Maestros/CategoriasGasto` | GET | Vista maestro de categorías. |
| `getDatatableServerSide` | `/Maestros/CategoriasGasto/GetDatatableServerSide` | GET | DataTable de categorías. |
| `store` | `/Maestros/CategoriasGasto/Store` | POST | Crea categoría (nombre único, máx. 50 chars). |
| `update` | `/Maestros/CategoriasGasto/Update/{id}` | PUT | Renombra categoría. |

---

## TiposPropiedadController

| Método | Ruta | HTTP | Descripción |
|--------|------|------|-------------|
| `index` | `/Maestros/TiposPropiedad` | GET | Vista maestro de tipos de propiedad. |
| `getDatatableServerSide` | `/Maestros/TiposPropiedad/GetDatatableServerSide` | GET | DataTable de tipos. |
| `store` | `/Maestros/TiposPropiedad/Store` | POST | Crea tipo (nombre único). |
| `update` | `/Maestros/TiposPropiedad/Update/{id}` | PUT | Renombra tipo. |

---

## Servicios

### YearlyCounterService

Genera números correlativos por usuario y año con bloqueo pesimista (`lockForUpdate`).

| Método | Descripción |
|--------|-------------|
| `nextTripNumber($userId, $anio)` | Incrementa y devuelve el siguiente `trip_number`. |
| `nextExpenseNumber($userId, $anio)` | Incrementa y devuelve el siguiente `expense_number`. |

### VehicleRentalService

Lógica de cuota/alquiler según tipo de propiedad (`ALQUILADO`, `FINANCIADO`).

| Método | Descripción |
|--------|-------------|
| `isRentedVehicle($vehicle)` | `true` si el tipo exige cuota periódica. |
| `suggestDailyRental($vehicle, $fecha)` | Prorrateo diario según periodo: diario, semanal (/7), quincenal (/14), mensual (/días del mes). |
| `suggestMonthlyRental($vehicle)` | Tope del periodo en resumen mensual. |
| `buildTripRentalSuggestion(...)` | Calcula apartado: `% del ingreso`, tope del periodo, tope opcional por viaje y 100% del ingreso si aplica. |
| `validateTripRental($vehicle, $alquiler, $baseIngreso)` | Valida alquiler en vehículos alquilados/financiados y que no supere el ingreso del viaje. |
| `vehicleMeta(...)` | Paquete para el frontend: sugerencia, % aplicado, topes y flags de aviso. |

### ClientesController / MicrobusRoutesController

| Controlador | Descripción |
|-------------|-------------|
| `ClientesController` | CRUD de cartera, dependientes, Select2 y ubicación opcional. |
| `MicrobusRoutesController` | Rutas, pasajeros (cliente o nombre libre), pagos mensuales por pasajero. |

Viajes aceptan `client_id`, `client_dependent_id` y `client_display_name` (obligatorio cliente en microbús individual).

### Select2Response

| Método | Descripción |
|--------|-------------|
| `fromPaginator($paginator, $mapper)` | Formato `{ results: [...], pagination: { more: bool } }` para Select2 AJAX. |
| `fromCollection($items, $mapper, $hasMore)` | Misma estructura desde una colección estática. |
