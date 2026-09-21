import { Router, Request, Response, NextFunction } from "express";
import { userController } from "../controllers/user.controller";
import { validate } from "../middlewares/validate.middleware";
import {
  userParamsSchema,
  updateUserSchema,
  UpdateUserInput,
} from "../validators/user.validator";

const router = Router();

/**
 * @route   PATCH /api/users/:id
 * @route   PUT /api/users/:id
 * @desc    Actualización de datos de un usuario existente
 * @access  Privado
 */
router.patch(
  "/:id",
  validate(userParamsSchema, "params"),
  validate(updateUserSchema, "body"),
  (req: Request, res: Response, next: NextFunction) => {
    userController.update(
      req as unknown as Request<{ id: string }, {}, UpdateUserInput>,
      res,
      next
    );
  }
);

router.put(
  "/:id",
  validate(userParamsSchema, "params"),
  validate(updateUserSchema, "body"),
  (req: Request, res: Response, next: NextFunction) => {
    userController.update(
      req as unknown as Request<{ id: string }, {}, UpdateUserInput>,
      res,
      next
    );
  }
);

export default router;
