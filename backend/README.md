# CampusGate - Backend API
Sistema de Control de Ingreso a Exámenes Masivos (DeltaSoft)

---

## Módulo de Verificación de Identidad (VER-01)
### Especificación del Token QR y Payload Criptográfico (S3-VER01-01)

#### 1. Formato del Token QR
El código QR de cada estudiante codifica una cadena compacta firmada criptográficamente con la estructura:
```text
<payload_base64url>.<firma_hmac_base64url>
```
- **Separador:** Un punto (`.`).
- **Codificación:** Base64URL sin relleno de igualdad (`=`) conforme al estándar RFC 4648 §5 (`+` -> `-`, `/` -> `_`).
- **Firma digital:** HMAC-SHA256 calculado sobre `<payload_base64url>` utilizando la clave secreta de la aplicación (`APP_KEY`).

#### 2. Esquema del Payload JSON
Antes de ser codificado en Base64URL, el payload es un objeto JSON estricto:
```json
{
  "v": 1,
  "sid": 123
}
```

| Campo | Tipo | Descripción |
| :--- | :--- | :--- |
| `v` | `integer` | Versión del formato del token (actualmente versión `1`). |
| `sid` | `integer` | Identificador único del estudiante (`id_estudiante` en base de datos). |

> **Principio de Privacidad y Protección de Datos:**  
> El payload **no incluye datos personales en texto claro** (sin CI, sin nombres, sin código SIS ni correos). Esto previene la filtración de datos sensibles si el código QR es fotografiado o leído por lectores ópticos genéricos.

#### 3. Consumo desde el Frontend y Dispositivos Móviles
1. **Lectura Óptica:** El escáner QR de la cámara o lector móvil extrae la cadena completa de texto (`payload.firma`).
2. **Tratamiento como Token Opaco:** El Frontend **no debe** intentar decodificar, validar ni modificar el token en el cliente. Debe enviarlo íntegro en el cuerpo de la petición hacia el endpoint de verificación:
   ```json
   POST /api/verificaciones/qr
   {
     "token": "eyJ2IjoxLCJzaWQiOjQyfQ.FIRMA_HMAC_BASE64URL",
     "examen_id": 5
   }
   ```
3. El backend se encarga de validar la firma criptográfica en tiempo constante (`hash_equals`), extraer el `id_estudiante`, comprobar su habilitación en el examen correspondiente y devolver la respuesta.

#### 4. Endpoint de Generación de Código QR

```http
GET /api/estudiantes/{id}/qr
```

- **Autenticación:** Obligatoria (`auth:sanctum`).
- **Autorización:** Exclusivo para el rol `ADMINISTRADOR` (middleware `role:ADMINISTRADOR`).
- **Parámetros de Ruta:**
  - `id`: Identificador entero del estudiante.
- **Respuestas:**
  - **`200 OK`**: Retorna el código QR vectorial en formato SVG listo para incrustar, previsualizar o descargar.
    - Cabecera: `Content-Type: image/svg+xml`
    - Cabecera: `Content-Disposition: inline; filename="qr-estudiante-{id}.svg"`
  - **`401 Unauthorized`**: Petición sin token Sanctum o sesión revocada/invalida.
  - **`403 Forbidden`**: Si el rol autenticado no es `ADMINISTRADOR` (ej. `DOCENTE` o `CONTROL_INGRESO`).
  - **`404 Not Found`**: Si el estudiante no existe (`{"message": "Estudiante no encontrado."}`).

---

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling.
