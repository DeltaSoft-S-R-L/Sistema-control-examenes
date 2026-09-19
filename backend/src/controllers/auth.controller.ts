import { Request, Response, NextFunction } from "express";
import { authService } from "../services/auth.service";
import { RegisterInput } from "../validators/auth.validator";

export class AuthController {
  /**
   * Controlador para el registro de nuevos usuarios.
   * Responde con código HTTP 201 Created y los datos públicos del usuario.
   */
  async register(
    req: Request<{}, {}, RegisterInput>,
    res: Response,
    next: NextFunction
  ): Promise<void> {
    try {
      const user = await authService.register(req.body);

      res.status(201).json({
        message: "Usuario registrado exitosamente",
        usuario: user,
      });
    } catch (error) {
      next(error);
    }
  }
}

export const authController = new AuthController();
