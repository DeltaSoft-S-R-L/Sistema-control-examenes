import { Request, Response, NextFunction } from "express";
import { ZodSchema } from "zod";

/**
 * Middleware para validar cualquier parte de la petición (body, params, query) contra un esquema de Zod.
 */
export const validate = (
  schema: ZodSchema,
  source: "body" | "params" | "query" = "body"
) => {
  return async (req: Request, _res: Response, next: NextFunction): Promise<void> => {
    try {
      req[source] = await schema.parseAsync(req[source]);
      next();
    } catch (error) {
      next(error);
    }
  };
};
