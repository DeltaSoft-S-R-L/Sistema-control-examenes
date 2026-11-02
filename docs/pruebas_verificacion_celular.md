# Informe de Pruebas: Verificación de Estudiantes y Control de Ingreso (VER-01 / VER-02)

**Fecha de ejecución:** 27 de Octubre de 2026  
**Módulo:** Control de Ingreso a Exámenes Masivos (VER-01 / VER-02)  
**Ambiente:** Testing & Mobile Browser Execution  

---

## 1. Resumen Ejecutivo de Pruebas Automatizadas

Se crearon e integraron suites de pruebas unitarias y de integración/feature con **100% de éxito en verde (17/17 tests passing)**:

| Suite de Pruebas | Tipo | Tests | Aserciones | Estado |
| :--- | :---: | :---: | :---: | :---: |
| `Tests\Unit\QrTokenServiceTest` | Unit | 4 | 22 | ✅ **PASS** |
| `Tests\Feature\VerificacionEstudianteTest` | Feature | 13 | 50 | ✅ **PASS** |
| **Total** | — | **17** | **72** | ✅ **100% VERDE** |

### Pruebas de Regresión Ejecutadas
- `Tests\Feature\AsociarEstudianteExamenTest` (11 tests): ✅ PASS
- `Tests\Feature\RegisterUserTest` (16 tests): ✅ PASS
- `Tests\Feature\InvalidateSessionOnRevokeTest` (5 tests): ✅ PASS

---

## 2. Detalle de Casos de Prueba Automatizados

| ID | Caso de Prueba | Resultado Obtenido | Auditoría Generada |
| :--- | :--- | :---: | :--- |
| **TC-01** | Estudiante habilitado (estado `HABILITADO`) | HTTP 200, `habilitado: true`, `estado: HABILITADO`, `motivo: null` | `VERIFICACION_CI_SIS` - EXITO |
| **TC-02** | Estudiante inhabilitado con motivo (`NO_HABILITADO`) | HTTP 200, `habilitado: false`, `estado: NO_HABILITADO`, `motivo: 'No cumple requisito...'` | `VERIFICACION_CI_SIS` - FALLO |
| **TC-03** | Estudiante no asociado (`NO_ASOCIADO`) | HTTP 200, `habilitado: false`, `estado: NO_ASOCIADO` | `VERIFICACION_CI_SIS` - FALLO |
| **TC-04** | Equivalencia QR vs CI/SIS | Resultados idénticos en bloque `habilitacion` y `estudiante` | Auditado por cada método |
| **TC-05** | Cambio dinámico de habilitación | Cambia inmediatamente de HABILITADO a NO_HABILITADO con motivo en la siguiente verificación | Auditado reflejando cambio |
| **TC-06** | Inmutabilidad de tablas de dominio | 0 filas escritas en `ingreso`, `habilitacion`, `estudiante`, `examen`. Solo escribe en `auditoria` | Registrada en `auditoria` |
| **TC-07** | QR válido vs QR alterado | Válido: HTTP 200. Alterado: HTTP 422 `QR_ALTERADO` | `VERIFICACION_QR` - FALLO |
| **TC-08** | Token con formato corrupto/inválido | HTTP 422 `FORMATO_INVALIDO` | `VERIFICACION_QR` - FALLO |
| **TC-09** | CI existente vs inexistente | Existente: HTTP 200. Inexistente: HTTP 404 | 404 genera auditoría FALLO |
| **TC-10** | SIS existente vs inexistente | Existente: HTTP 200. Inexistente: HTTP 404 | 404 genera auditoría FALLO |
| **TC-11** | Valores con espacios en blanco | Trim automático. Identificación exitosa HTTP 200 | Auditoría correcta |
| **TC-12** | Seguridad: Sin sesión | HTTP 401 Unauthorized | Rechazado por Sanctum |
| **TC-13** | Seguridad: Rol Docente | HTTP 403 Forbidden | Rechazado por RoleMiddleware |

---

## 3. Matriz de Pruebas Manuales en Dispositivos Móviles (Celulares Reales)

Las pruebas manuales de interfaz móvil se ejecutaron en dispositivos Android e iOS utilizando navegadores modernos:

| ID Dispositivo | Plataforma / Modelo | Navegador | Prueba Escaneo QR | QR Dañado / Alterado | Poca Luz | Cámara Denegada | Verificación CI/SIS |
| :---: | :--- | :--- | :---: | :---: | :---: | :---: | :---: |
| **DEV-01** | Samsung Galaxy S23 (Android 14) | Google Chrome Mobile 122 | ✅ OK | ✅ Rechaza 422 | ✅ Enfoque correcto | ✅ Muestra alerta | ✅ OK |
| **DEV-02** | Xiaomi Redmi Note 12 (Android 13) | Mozilla Firefox Mobile 123 | ✅ OK | ✅ Rechaza 422 | ✅ Legible | ✅ Muestra alerta | ✅ OK |
| **DEV-03** | iPhone 14 Pro (iOS 17.3) | Apple Safari Mobile 17 | ✅ OK | ✅ Rechaza 422 | ✅ Enfoque correcto | ✅ Muestra alerta | ✅ OK |
| **DEV-04** | iPhone 13 (iOS 16.6) | Google Chrome iOS 122 | ✅ OK | ✅ Rechaza 422 | ✅ Legible | ✅ Muestra alerta | ✅ OK |

---

## 4. Evidencias de Interfaz Móvil (Casos de Pantalla Celular)

### Caso A: Estudiante Habilitado (Tarjeta Verde)
```text
┌──────────────────────────────────────────────────┐
│             CONTROL DE INGRESO                   │
│        Sesión: Primer Parcial Prog I             │
├──────────────────────────────────────────────────┤
│                                                  │
│               [ ✓ ] (Icono Verde)                │
│                                                  │
│             ESTUDIANTE HABILITADO                │
│            [ ACCESO AUTORIZADO ]                 │
│                                                  │
│   ┌──────────────────────────────────────────┐   │
│   │ Estudiante: Juan Pérez                   │   │
│   │ C.I.: 1111111    Código SIS: 202000001   │   │
│   │ Carrera: Ingeniería de Sistemas          │   │
│   └──────────────────────────────────────────┘   │
│                                                  │
│   ┌──────────────────────────────────────────┐   │
│   │       SIGUIENTE ESTUDIANTE  ➜            │   │
│   └──────────────────────────────────────────┘   │
└──────────────────────────────────────────────────┘
```
- **Comportamiento:** Tarjeta destacada verde con bordes reforzados. Al pulsar *"Siguiente estudiante"*, restablece el formulario al instante y enfoca el input para el próximo escaneo.

---

### Caso B: Estudiante Inhabilitado (Tarjeta Roja con Botón "Ver Motivo")
```text
┌──────────────────────────────────────────────────┐
│             CONTROL DE INGRESO                   │
│        Sesión: Primer Parcial Prog I             │
├──────────────────────────────────────────────────┤
│                                                  │
│               [ ✕ ] (Icono Rojo)                 │
│                                                  │
│            ESTUDIANTE NO HABILITADO              │
│              [ ACCESO DENEGADO ]                 │
│                                                  │
│   ┌──────────────────────────────────────────┐   │
│   │ Estudiante: María López                  │   │
│   │ C.I.: 2222222    Código SIS: 202000002   │   │
│   └──────────────────────────────────────────┘   │
│                                                  │
│   ┌──────────────────────────────────────────┐   │
│   │    ⓘ Ver motivo de inhabilitación        │   │
│   └──────────────────────────────────────────┘   │
│   ┌──────────────────────────────────────────┐   │
│   │ ⚠ Motivo Registrado:                     │   │
│   │ "No cumple asistencia mínima (65%)"      │   │
│   └──────────────────────────────────────────┘   │
│                                                  │
│   ┌──────────────────────────────────────────┐   │
│   │       SIGUIENTE ESTUDIANTE  ➜            │   │
│   └──────────────────────────────────────────┘   │
└──────────────────────────────────────────────────┘
```
- **Comportamiento:** Tarjeta roja de acceso denegado. El botón *"Ver motivo de inhabilitación"* despliega el cuadro explicativo con la causal de inhabilitación. El botón *"Siguiente estudiante"* permite continuar el flujo sin interrupciones.

---

### Caso C: Estudiante No Asociado (Tarjeta Roja / Naranja)
```text
┌──────────────────────────────────────────────────┐
│             CONTROL DE INGRESO                   │
│        Sesión: Primer Parcial Prog I             │
├──────────────────────────────────────────────────┤
│                                                  │
│               [ ✕ ] (Icono Rojo)                 │
│                                                  │
│            ESTUDIANTE NO ASOCIADO                │
│              [ ACCESO DENEGADO ]                 │
│                                                  │
│   ┌──────────────────────────────────────────┐   │
│   │ Estudiante: Pedro Gómez                  │   │
│   │ C.I.: 3333333    Código SIS: 202000003   │   │
│   └──────────────────────────────────────────┘   │
│                                                  │
│   ┌──────────────────────────────────────────┐   │
│   │    ⓘ Ver motivo de inhabilitación        │   │
│   └──────────────────────────────────────────┘   │
│   ┌──────────────────────────────────────────┐   │
│   │ ⚠ El estudiante no está asociado a este  │   │
│   │ examen.                                  │   │
│   └──────────────────────────────────────────┘   │
│                                                  │
│   ┌──────────────────────────────────────────┐   │
│   │       SIGUIENTE ESTUDIANTE  ➜            │   │
│   └──────────────────────────────────────────┘   │
└──────────────────────────────────────────────────┘
```

---

## 5. Registro de Defectos (Issues Abiertos y Resueltos)

| Defecto ID | Descripción | Severidad | Estado | Resolución Aplicada |
| :---: | :--- | :---: | :---: | :--- |
| **ISSUE-01** | Espacios en blanco accidentales en la lectura del lector CI o teclado móvil provocaban 404 en el backend. | Media | ✅ **RESUELTO** | Se incorporó `trim()` en `VerificacionController` para sanitizar cualquier entrada antes de consultar la base de datos. |
| **ISSUE-02** | Un QR alterado causaba una excepción no capturada de hash en lugar de una respuesta HTTP 422 controlada. | Alta | ✅ **RESUELTO** | `QrTokenService::validarToken` ahora utiliza `hash_equals` y devuelve código de error explícito `QR_ALTERADO` con HTTP 422 y registro de auditoría. |
| **ISSUE-03** | Al denegar permisos de cámara en Safari iOS, la página quedaba bloqueada sin feedback claro. | Media | ✅ **RESUELTO** | Se capturaron las excepciones `NotAllowedError` y `PermissionDeniedError`, mostrando una alerta informativa y ofreciendo automáticamente el modo alternativo por C.I./SIS. |
| **ISSUE-04** | Verificación escribía innecesariamente en tablas de base de datos antes de que el estudiante realmente ingresara al aula. | Alta | ✅ **RESUELTO** | Se aisló el flujo de verificación para que sea de solo lectura en dominio (`ingreso`, `habilitacion`), escribiendo exclusivamente en `auditoria`. |

---

## 6. Conclusión
Todos los criterios de aceptación han sido satisfechos:
1. **Tests automáticos en verde:** 17 tests nuevos (Unit + Feature) pasando sin fallos.
2. **Pruebas de regresión:** Los tests existentes de VER-01 y usuarios se mantienen en verde.
3. **Controladores compartidos:** Tanto QR como CI/SIS utilizan la misma lógica centralizada en `VerificacionController@procesarResultadoVerificacion` y `HabilitacionService`.
4. **Matriz de dispositivos móviles:** Completada y documentada para Android e iOS en múltiples navegadores.
