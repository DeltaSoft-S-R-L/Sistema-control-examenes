# 🎓 Sistema de Control de Exámenes

Sistema integral para el control de asistencia, verificación de habilitación, asignación de ambientes y registro de incidencias en exámenes académicos.

---

## 🛠️ Tecnologías Utilizadas

- **Frontend:**
  - React.js (con Vite)
  - HTML5 & CSS3
  - Bootstrap 5 + Bootstrap Icons
  - React Router DOM
  - Axios
- **Backend:**
  - Laravel 11
  - PHP 8.2+
  - Laravel Sanctum (Autenticación API)
- **Base de Datos:**
  - PostgreSQL 16 (con soporte a esquemas, triggers y constraints)

---

## 📁 Estructura del Proyecto

```text
Sistema-control-examenes/
├── backend/                  # API RESTful en Laravel 11 (PHP 8.2)
│   ├── app/Models/           # Modelos Eloquent (17 tablas y relaciones)
│   ├── app/Http/Controllers/ # Controladores API (Auth, Examen, Estudiante, etc.)
│   ├── routes/api.php        # Rutas y endpoints del API
│   └── .env                  # Configuración de entorno de Laravel
├── frontend/                 # Aplicación SPA en React.js + Bootstrap 5
│   ├── src/pages/            # Vistas (Login, Dashboard, Exámenes, Estudiantes, etc.)
│   ├── src/components/       # Componentes reutilizables (Navbar, Sidebar, Layout)
│   ├── src/services/api.js   # Cliente HTTP Axios configurado con Bearer Token
│   └── .env                  # Variables de entorno de Vite
├── database/                 # Scripts SQL de estructura, triggers y seeders
│   ├── 01_schema.sql
│   ├── 02_constraints.sql
│   ├── 03_triggers.sql
│   ├── 04_seed.sql
│   └── 05_test.sql
└── docker-compose.yml        # Orquestación de la base de datos PostgreSQL
```

---

## 🚀 Requisitos Previos

1. **PHP >= 8.2** y **Composer** instalados.
   - *Nota:* Asegúrate de tener habilitadas las extensiones `pdo_pgsql`, `pgsql` y `zip` en tu archivo `php.ini`.
2. **Node.js >= 18** y **npm** instalados.
3. **PostgreSQL** o **Docker Desktop** (para levantar la BD con Docker).

---

## ⚙️ Guía de Instalación y Uso

### 1. Base de Datos (PostgreSQL)

#### Opción A: Usando Docker (Recomendada y rápida)
Ejecuta en la raíz del proyecto para crear la BD y cargar las tablas automáticamente:
```bash
docker compose up -d
```

#### Opción B: Usando PostgreSQL Local / pgAdmin
1. Crea una base de datos llamada `control_examenes`.
2. Ejecuta en orden los scripts ubicados en la carpeta `database/`:
   1. `01_schema.sql`
   2. `02_constraints.sql`
   3. `03_triggers.sql`
   4. `04_seed.sql`

---

### 2. Backend (Laravel 11)

1. Abre una terminal y navega a la carpeta del backend:
   ```bash
   cd backend
   ```

2. Instala las dependencias (si no se han instalado):
   ```bash
   composer install
   ```

3. Configura el archivo `.env`:
   - Si no existe, copia `.env.example`:
     ```bash
     copy .env.example .env
     ```
   - Verifica las credenciales de tu base de datos en `.env`:
     ```env
     DB_CONNECTION=pgsql
     DB_HOST=127.0.0.1
     DB_PORT=5432
     DB_DATABASE=control_examenes
     DB_USERNAME=postgres
     DB_PASSWORD=tu_password
     ```

4. Genera la clave de la aplicación:
   ```bash
   php artisan key:generate
   ```

5. Inicia el servidor de desarrollo del backend:
   ```bash
   php artisan serve
   ```
   *El servidor quedará disponible en:* `http://127.0.0.1:8000`

---

### 3. Frontend (React.js + Bootstrap 5)

1. Abre otra terminal y navega a la carpeta del frontend:
   ```bash
   cd frontend
   ```

2. Instala las dependencias:
   ```bash
   npm install
   ```

3. Inicia el servidor de desarrollo:
   ```bash
   npm run dev
   ```
   *La aplicación estará accesible en:* `http://localhost:5173`

---

## 🔑 Endpoints Principales del Backend

| Método | Endpoint | Descripción | Requiere Auth |
| :--- | :--- | :--- | :---: |
| `GET` | `/api/health` | Estado del servicio | No |
| `POST` | `/api/login` | Inicio de sesión y token | No |
| `POST` | `/api/logout` | Cierre de sesión y revocación | Sí |
| `GET` | `/api/me` | Datos del usuario conectado | Sí |
| `GET/POST` | `/api/examenes` | CRUD de Exámenes | Sí |
| `GET/POST` | `/api/estudiantes` | Listado con filtros y registro de Estudiantes | Sí |
| `GET` | `/api/estudiantes/buscar` | Búsqueda exacta por CI o código SIS (`?ci=&codigo_sis=`) | Sí |
| `PUT/PATCH` | `/api/estudiantes/{id}` | Modificación de datos administrables de estudiante | Sí |
| `DELETE` | `/api/estudiantes/{id}` | Eliminación de estudiante | Sí |
| `POST` | `/api/estudiantes/carga-masiva` | Carga masiva de estudiantes desde archivo CSV | Sí |
| `GET/POST` | `/api/ambientes` | CRUD de Ambientes/Aulas | Sí |
| `GET/POST` | `/api/asignaturas` | CRUD de Asignaturas | Sí |
| `GET/POST` | `/api/habilitaciones` | Control de Habilitaciones | Sí |
| `GET/POST` | `/api/ingresos` | Registro de Ingreso a examen | Sí |
| `GET/POST` | `/api/incidencias` | Registro de Incidencias | Sí |

---

## 📑 Carga Masiva de Estudiantes (CSV / Excel)

El endpoint `/api/estudiantes/carga-masiva` procesa archivos `.csv` delimitados por coma (desde Excel se puede guardar/exportar como CSV delimitado por comas):

- **Columnas obligatorias:**
  - `ci`: Cédula de Identidad (máx. 20 caracteres, único).
  - `nombre`: Nombre del estudiante (máx. 100 caracteres).
  - `apellido`: Apellido del estudiante (máx. 100 caracteres).
  - `codigo_universitario`: Código SIS / universitario (máx. 50 caracteres, único).
- **Columnas opcionales:**
  - `correo`: Correo electrónico institucional o personal (formato email).
  - `estado`: `ACTIVO` o `INACTIVO` (por defecto `ACTIVO`).

### Reglas de validación:
1. **Validación de duplicados contra la base de datos:** Si el CI o código universitario ya existen en el sistema, la fila se rechaza con un mensaje claro indicando el motivo exacto.
2. **Validación de duplicados dentro del mismo archivo:** Si un CI o código viene repetido varias veces en el archivo, se rechazan las ocurrencias posteriores.
3. **Resumen de respuesta:** Retorna un reporte JSON con el total de filas procesadas, cantidad de insertados, cantidad de rechazados y el detalle fila por fila con el motivo de rechazo.

