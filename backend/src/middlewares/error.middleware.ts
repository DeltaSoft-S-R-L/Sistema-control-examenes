import { Request, Response, NextFunction, ErrorRequestHandler } from "express";
import { ZodError } from "zod";
import { Prisma } from "@prisma/client";
import { AppError } from "../utils/app-error";

/**
 * Middleware centralizado para el manejo de excepciones y errores de la API.
 */
export const errorHandler: ErrorRequestHandler = (
  err: Error,
  _req: Request,
  res: Response,
  _next: NextFunction
): void => {
  // 1. Errores de validación con Zod
  if (err instanceof ZodError) {
    const issues = err.issues.map((issue) => ({
      campo: issue.path.join("."),
      mensaje: issue.message,
    }));

    res.status(400).json({
      error: "Error de validación",
      detalles: issues,
    });
    return;
  }

  // 2. Errores operacionales controlados (AppError)
  if (err instanceof AppError) {
    res.status(err.statusCode).json({
      error: err.message,
      ...(err.details ? { detalles: err.details } : {}),
    });
    return;
  }

  // 3. Excepciones conocidas de Prisma ORM (ej. condiciones de carrera en unicidad)
  if (err instanceof Prisma.PrismaClientKnownRequestError) {
    if (err.code === "P2002") {
      const target = Array.isArray(err.meta?.target)
        ? (err.meta?.target as string[]).join(", ")
        : (err.meta?.target as string) || "campo único";

      let campo = target;
      if (target.includes("correo")) {
        campo = "correo electrónico";
      } else if (target.includes("username")) {
        campo = "nombre de usuario";
      }

      res.status(409).json({
        error: `Conflicto de unicidad: ya existe un registro con el mismo valor en: ${campo}`,
      });
      return;
    }
  }

  // 4. Errores de sintaxis en JSON entrante
  if (err instanceof SyntaxError && "body" in err) {
    res.status(400).json({
      error: "Formato JSON inválido en el cuerpo de la petición",
    });
    return;
  }

  // 5. Errores no controlados (500)
  console.error("❌ Unhandled Error:", err);
  res.status(500).json({
    error: "Error interno del servidor",
  });
};
