import bcrypt from "bcryptjs";
import { prisma } from "../lib/prisma";
import { AppError } from "../utils/app-error";
import { RegisterInput } from "../validators/auth.validator";

export interface CreatedUserResponse {
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

export class AuthService {
  private readonly SALT_ROUNDS = 10;

  /**
   * Realiza el registro completo de un nuevo usuario en el sistema.
   *
   * @param input Datos validados del usuario a registrar
   * @returns Datos del usuario creado excluyendo la contraseña
   */
  async register(input: RegisterInput): Promise<CreatedUserResponse> {
    const { nombre, apellido, correo, username, password, rol } = input;

    // 1. Validar que el correo no esté registrado previamente
    const existingEmail = await prisma.usuario.findUnique({
      where: { correo },
    });
    if (existingEmail) {
      throw new AppError("El correo electrónico ya se encuentra registrado", 409);
    }

    // 2. Validar que el nombre de usuario no esté registrado previamente
    const existingUsername = await prisma.usuario.findUnique({
      where: { username },
    });
    if (existingUsername) {
      throw new AppError("El nombre de usuario ya está en uso", 409);
    }

    // 3. Buscar el rol solicitado en la base de datos (insensible a mayúsculas/minúsculas)
    const roleRecord = await prisma.rol.findFirst({
      where: {
        nombre: {
          equals: rol,
          mode: "insensitive",
        },
      },
    });

    if (!roleRecord) {
      throw new AppError(
        `El rol '${rol}' no es válido o no existe en el sistema`,
        400
      );
    }

    // 4. Hashear la contraseña de forma asíncrona
    const password_hash = await bcrypt.hash(password, this.SALT_ROUNDS);

    // 5. Persistir el nuevo usuario en PostgreSQL con estado ACTIVO
    const createdUser = await prisma.usuario.create({
      data: {
        nombre,
        apellido,
        correo,
        username,
        password_hash,
        id_rol: roleRecord.id_rol,
        estado: "ACTIVO",
      },
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

    return createdUser;
  }
}

export const authService = new AuthService();
