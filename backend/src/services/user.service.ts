import { Prisma } from "@prisma/client";
import { prisma } from "../lib/prisma";
import { AppError } from "../utils/app-error";
import { UpdateUserInput } from "../validators/user.validator";

export interface UserResponse {
  id_usuario: bigint;
  nombre: string;
  apellido: string;
  correo: string;
  username: string;
  id_rol: bigint;
  estado: string;
  rol: {
    id_rol: bigint;
    nombre: string;
  };
}

export class UserService {
  /**
   * Actualiza parcialmente un usuario existente en la base de datos.
   *
   * @param id_usuario Identificador único del usuario (BigInt)
   * @param input Datos validados a actualizar
   * @returns Datos del usuario actualizado sin password_hash
   */
  async update(id_usuario: bigint, input: UpdateUserInput): Promise<UserResponse> {
    // 1. Verificar existencia del usuario
    const existingUser = await prisma.usuario.findUnique({
      where: { id_usuario },
    });

    if (!existingUser) {
      throw new AppError("Usuario no encontrado", 404);
    }

    // 2. Validar que el nuevo correo no esté tomado por otro usuario
    if (input.correo && input.correo !== existingUser.correo) {
      const emailCollision = await prisma.usuario.findFirst({
        where: {
          correo: input.correo,
          NOT: { id_usuario },
        },
      });

      if (emailCollision) {
        throw new AppError(
          "El correo electrónico ya está registrado por otro usuario",
          409
        );
      }
    }

    // 3. Validar que el nuevo username no esté tomado por otro usuario
    if (input.username && input.username !== existingUser.username) {
      const usernameCollision = await prisma.usuario.findFirst({
        where: {
          username: input.username,
          NOT: { id_usuario },
        },
      });

      if (usernameCollision) {
        throw new AppError("El nombre de usuario ya está en uso", 409);
      }
    }

    // 4. Validar existencia del rol si se especifica
    let roleId: bigint | undefined;
    if (input.rol) {
      const roleRecord = await prisma.rol.findFirst({
        where: {
          nombre: {
            equals: input.rol,
            mode: "insensitive",
          },
        },
      });

      if (!roleRecord) {
        throw new AppError(
          "El rol especificado no es válido o no existe",
          400
        );
      }

      roleId = roleRecord.id_rol;
    }

    // 5. Construir objeto de actualización seguro (sin permitir modificar contraseña)
    const updateData: Prisma.usuarioUpdateInput = {};

    if (input.nombre !== undefined) updateData.nombre = input.nombre;
    if (input.apellido !== undefined) updateData.apellido = input.apellido;
    if (input.correo !== undefined) updateData.correo = input.correo;
    if (input.username !== undefined) updateData.username = input.username;
    if (input.estado !== undefined) updateData.estado = input.estado;
    if (roleId !== undefined) {
      updateData.rol = {
        connect: { id_rol: roleId },
      };
    }

    // 6. Persistir cambios en PostgreSQL excluyendo password_hash
    const updatedUser = await prisma.usuario.update({
      where: { id_usuario },
      data: updateData,
      select: {
        id_usuario: true,
        nombre: true,
        apellido: true,
        correo: true,
        username: true,
        id_rol: true,
        estado: true,
        rol: {
          select: {
            id_rol: true,
            nombre: true,
          },
        },
      },
    });

    return updatedUser;
  }
}

export const userService = new UserService();
