import { Request, Response, NextFunction } from "express";
import { userService } from "../services/user.service";
import { UpdateUserInput } from "../validators/user.validator";

export class UserController {
  /**
   * Controlador para la actualización de datos de un usuario.
   * Responde con HTTP 200 OK y los datos públicos del usuario actualizado.
   */
  async update(
    req: Request<{ id: string }, {}, UpdateUserInput>,
    res: Response,
    next: NextFunction
  ): Promise<void> {
    try {
      const id_usuario = BigInt(req.params.id);
      const updatedUser = await userService.update(id_usuario, req.body);

      res.status(200).json({
        message: "Usuario actualizado exitosamente",
        usuario: updatedUser,
      });
    } catch (error) {
      next(error);
    }
  }
}

export const userController = new UserController();
