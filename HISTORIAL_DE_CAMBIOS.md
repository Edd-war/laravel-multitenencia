# Historial de Cambios

Todos los cambios notables en `laravel-inquilinos` (anteriormente `laravel-multitenencia`) serán documentados en este archivo.

## 4.1.1 - 2026-04-12

### Qué ha cambiado

- Añadir descripción al ComandoArtisanInquilinos por @dac514.

## 4.0.9 - 2026-02-09

### Qué ha cambiado

- Corrección #617: Añadir valor por defecto para el parámetro del constructor de ColeccionDeTareas por @freekmurze.
- Corrección #620: Manejar JobRetryRequested en la detección del inquilino en colas por @freekmurze.

## 4.0.8 - 2026-02-09

### Qué ha cambiado

- Corrección del contexto del inquilino que no se restauraba cuando ocurría una excepción en `execute()` por @moisish.
- Compatibilidad de aserciones de pruebas de notificación para Laravel 11/12.

## 4.0.7 - 2025-09-26

### Qué ha cambiado

- Corregir sintaxis para la configuración de la interfaz que reconoce inquilinos por @danharrin.

## 4.0.6 - 2025-09-26

### Qué ha cambiado

- Persistencia del estado del comando con `InquilinoReconocido` por @SethSharp.
- Añadir tipos a InquilinoCollection por @markieo1.
- Añadir opción de configuración para especificar las interfaces `InquilinoReconocido` e `InquilinoNoReconocido` por @przemyslaw-przylucki.

## 4.0.5 - 2025-05-12

### Qué ha cambiado

- Corrección de errores: Los trabajos con `ShouldBeEncrypted` necesitan ser desencriptados por @Joel-Jensen.

## 4.0.4 - 2025-02-20

### Qué ha cambiado

- Compatibilidad con Laravel 12.x por @laravel-shift.

## 4.0.0 - 2024-07-24

### Qué ha cambiado

- Refactorizar el método registraBuscadorDeInquilinos para reducir la complejidad por @misaf.
- Reemplazar usos de Tenant con el contrato EsInquilino por @masterix21.

## 3.0.0 - 2022-10-28

### Cambios importantes

- Los trabajos `InquilinoNoReconocido` ahora olvidarán cualquier inquilino actual que pueda haber sido establecido al comenzar el procesamiento.

## 1.0.0 - 2020-05-16

- Lanzamiento inicial.
