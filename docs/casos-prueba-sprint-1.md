# Casos de prueba automatizados — Sprint 1

**Versión bajo prueba:** rama `codex/test-case`, basada en `origin/Acctualizada`.

**Cobertura:** autenticación y protección de API, alta/listado/búsqueda/filtro/edición de usuarios, alta/búsqueda/edición de estudiantes e importación CSV.

Los 20 casos de esta lista corresponden a los métodos de `backend/tests/Feature/SprintOneApiTest.php`. Se ejecutan contra los endpoints Laravel con una base SQLite en memoria, aislada de la base de datos de desarrollo.

## Casos

| ID | Prioridad | Escenario | Resultado esperado |
|---|---|---|---|
| TC-01 | P1 | Iniciar sesión con credenciales correctas de usuario activo | HTTP 200 con token y datos básicos de usuario; no se expone la contraseña. |
| TC-02 | P1 | Iniciar sesión con contraseña incorrecta o username inexistente | HTTP 422 con error de credenciales; no se autentica. |
| TC-03 | P1 | Iniciar sesión con usuario revocado | HTTP 422; no se emite token. |
| TC-04 | P1 | Consultar endpoints protegidos sin token | HTTP 401 en listado de usuarios y alta de estudiantes. |
| TC-05 | P1 | Crear usuario con datos válidos | HTTP 201; usuario persistido como activo y contraseña almacenada como hash. |
| TC-06 | P1 | Crear usuario con campos obligatorios, correo, contraseña o rol inválidos | HTTP 422 con los campos correspondientes; no se inserta la cuenta. |
| TC-07 | P1 | Crear usuario con correo o username ya registrados | HTTP 422; no se permite duplicar ninguno de los identificadores. |
| TC-08 | P1 | Listar más de 20 usuarios | 20 elementos en página 1 y el restante en página 2, sin perder registros. |
| TC-09 | P2 | Filtrar usuarios por rol | Solo se devuelven usuarios con el rol solicitado. |
| TC-10 | P2 | Buscar usuarios sin distinguir mayúsculas ASCII | Se encuentra la coincidencia por nombre; el filtro no devuelve registros ajenos. |
| TC-11 | P2 | Consultar usuario inexistente | HTTP 404. |
| TC-12 | P1 | Editar usuario válido y enviar estado en minúsculas | HTTP 200; datos guardados y estado normalizado a `REVOCADO`. |
| TC-13 | P1 | Editar usuario con correo de otra cuenta | HTTP 422; no se modifica el usuario. |
| TC-14 | P1 | Crear estudiante con datos válidos | HTTP 201 y registro persistido. |
| TC-15 | P1 | Crear estudiante con CI, correo o estado inválidos | HTTP 422; no se crea un registro parcial. |
| TC-16 | P1 | Crear estudiante con CI o código universitario repetidos | HTTP 422; se preserva el registro existente. |
| TC-17 | P2 | Buscar estudiante y filtrar por estado | Se devuelve solo el estudiante que coincide con búsqueda y estado. |
| TC-18 | P1 | Editar estudiante y cambiar estado en minúsculas | HTTP 200; cambios persistidos y estado normalizado a `INACTIVO`. |
| TC-19 | P1 | Importar CSV UTF-8 separado por punto y coma, con columna final vacía | HTTP 200; estudiante importado y caracteres UTF-8 conservados. |
| TC-20 | P1 | Importar encabezados incorrectos y un archivo con fila duplicada | Encabezados incorrectos devuelven HTTP 422; filas inválidas se informan y no se duplican datos. |

## Ejecución

Desde `backend/`:

```bash
php artisan test --filter=SprintOneApiTest
```

## Límites de esta suite

- Son pruebas de integración de API. No automatizan los clics ni la presentación visual de React; requieren una suite de navegador (por ejemplo, Playwright) si el sprint exige validar la interfaz de punta a punta.
- Se usa SQLite en memoria y un esquema mínimo declarado por el test. La ejecución no valida que todas las migraciones del entorno PostgreSQL funcionen.
- La búsqueda sensible a tildes debe verificarse contra PostgreSQL; la función `LOWER` de SQLite no normaliza mayúsculas Unicode de forma completa.
- Los criterios de permisos diferenciados por rol y el acceso por correo no están claros en el código/criterios disponibles; deben confirmarse antes de ampliar el alcance.
