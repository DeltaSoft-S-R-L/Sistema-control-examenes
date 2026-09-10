# Sistema de Control de Exámenes

Monorepo inicial para gestionar exámenes académicos.

## Estructura

- `frontend/`: aplicación web con Next.js y TypeScript.
- `backend/`: API con Express, TypeScript y Prisma.
- `docker-compose.yml`: frontend, backend y PostgreSQL.

## Inicio rápido con Docker

```bash
docker compose up --build
```

- Frontend: http://localhost:3000
- API: http://localhost:4000/health
- PostgreSQL: localhost:5432

## Desarrollo local

1. Copia `.env.example` a `.env`.
2. Instala dependencias dentro de `frontend` y `backend`.
3. Ejecuta `npm run dev` en cada proyecto.
4. En `backend`, ejecuta `npm run prisma:generate` y crea la migración inicial con `npm run prisma:migrate`.
