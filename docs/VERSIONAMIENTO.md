# Versionamiento

ConductorLedger usa **Versionado Semántico (SemVer)** con el formato `MAJOR.MINOR.PATCH` (ej. `1.0.0`).

## Archivos de referencia

| Archivo | Propósito |
|---------|-----------|
| [`VERSION`](../VERSION) | Versión actual (fuente principal, una línea) |
| [`CHANGELOG.md`](../CHANGELOG.md) | Historial de cambios por versión |
| [`config/conductor-ledger.php`](../config/conductor-ledger.php) | Expone `version` a la aplicación |
| [`composer.json`](../composer.json) | Versión del paquete para Composer |

La versión visible en la interfaz (sidebar) se lee desde `config('conductor-ledger.version')`.

## Cuándo incrementar

| Tipo | Cuándo | Ejemplo |
|------|--------|---------|
| **MAJOR** | Cambios incompatibles (API, BD, flujos de auth) | `1.0.0` → `2.0.0` |
| **MINOR** | Funcionalidad nueva compatible | `1.0.0` → `1.1.0` |
| **PATCH** | Correcciones de bugs, ajustes menores | `1.0.0` → `1.0.1` |

## Flujo al publicar una versión

1. Actualizar [`VERSION`](../VERSION) con el nuevo número.
2. Mover entradas de `[Unreleased]` a una sección fechada en [`CHANGELOG.md`](../CHANGELOG.md).
3. Actualizar `version` en [`composer.json`](../composer.json).
4. Commit con mensaje descriptivo, por ejemplo: `Release v1.1.0: descripción breve`.
5. Crear tag anotado:
   ```bash
   git tag -a v1.1.0 -m "Release v1.1.0: descripción breve"
   git push origin main --tags
   ```

## Comprobar versión en el servidor

```bash
php artisan about
cat VERSION
```

## Historial de versiones

| Versión | Fecha | Resumen |
|---------|-------|---------|
| **1.1.1** | 2026-07-08 | Fix creación vehículos (todos los tipos), correo al crear usuario |
| **1.1.0** | 2026-07-08 | Loaders AJAX, fix cifrado, edición vehículos ALQUILADO |
| **1.0.0** | 2026-07-08 | Seguridad (RBAC, cifrado, backups, correo), registro, i18n ES |
| **0.2.0** | 2026-07-05 | Diseño responsive móvil |
| **0.1.0** | 2026-07-04 | Release inicial |

Detalle completo en [CHANGELOG.md](../CHANGELOG.md).
