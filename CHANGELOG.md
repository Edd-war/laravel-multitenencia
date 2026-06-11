# Changelog & Upgrade Guide

## [2.1.0] - 2026-06-11

### Added
- **Multi-Strategy Resolution**: Added `BuscadorDeInquilinosPorHeaders` supporting header (`X-Sitio-Context`, `X-Sitio-ID`), query parameters (`sitio_id`, `dominio`), and Host fallbacks.
- **Auto-create database**: Added `crear_base_de_datos_si_no_existe` configuration setting. If enabled, the connection task automatically creates the physical database (SQLite, MySQL, etc.) if it is missing.
- **Tenant Database Prefix**: Added `prefijo_de_base_de_datos_del_inquilino` to automatically prefix tenant database names before connection resolution.
- **Context-Aware Caching**: Integrated `MultitenenciaCacheServiceProvider` for context-aware multi-tenant cache prefixing and dynamic database store switching.
- **Generic Owner Middleware**: Added `EsOrigenDelPropietario` middleware to limit routes to owner domains configured via `dominios_propietarios`.
- **Flexible Enforcer Middleware**: Added `RequiereOrigenInquilino` and `InquilinoResolver` as a package-supported pair for explicit request context enforcement.

### Changed (Breaking Changes)
- Normalized all config keys and terminology to Spanish (`inquilino`, `propietario`).
- Config key renames:
  - `tenant_migrations_path` -> `ruta_de_migraciones_del_inquilino`
  - `landlord_domains` -> `dominios_propietarios`
  - `tenant_database_connection_name` / old connection keys -> `nombre_de_conexion_de_la_base_de_datos_del_inquilino`
  - `landlord_database_connection_name` / old connection keys -> `nombre_de_conexion_de_la_base_de_datos_del_propietario`

---

## Upgrade Steps

1. **Update config keys**:
   Rename your keys in `config/multitenencia.php` to match the Spanish naming convention:
   ```php
   'ruta_de_migraciones_del_inquilino' => 'database/migrations/inquilinos',
   'nombre_de_conexion_de_la_base_de_datos_del_inquilino' => 'inquilino',
   'nombre_de_conexion_de_la_base_de_datos_del_propietario' => 'propietario',
   ```

2. **Register Owner Domain**:
   Use the `dominios_propietarios` array instead of `landlord_domains`.

3. **Database Prefix**:
   Set `prefijo_de_base_de_datos_del_inquilino` in `config/multitenencia.php` instead of computing it locally in the tenant model.
