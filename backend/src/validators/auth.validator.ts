import { z } from "zod";

/**
 * Esquema de validación para el registro de nuevos usuarios.
 */
export const registerSchema = z.object({
  nombre: z
    .string({ message: "El nombre es obligatorio" })
    .trim()
    .min(2, "El nombre debe tener al menos 2 caracteres")
    .max(100, "El nombre no puede exceder los 100 caracteres"),

  apellido: z
    .string({ message: "El apellido es obligatorio" })
    .trim()
    .min(2, "El apellido debe tener al menos 2 caracteres")
    .max(100, "El apellido no puede exceder los 100 caracteres"),

  correo: z
    .string({ message: "El correo electrónico es obligatorio" })
    .trim()
    .email("El formato del correo electrónico no es válido")
    .max(150, "El correo no puede exceder los 150 caracteres")
    .toLowerCase(),

  username: z
    .string({ message: "El nombre de usuario es obligatorio" })
    .trim()
    .min(3, "El nombre de usuario debe tener al menos 3 caracteres")
    .max(50, "El nombre de usuario no puede exceder los 50 caracteres")
    .regex(
      /^[a-zA-Z0-9_]+$/,
      "El nombre de usuario solo puede contener caracteres alfanuméricos y guiones bajos"
    ),

  password: z
    .string({ message: "La contraseña es obligatoria" })
    .min(8, "La contraseña debe tener al menos 8 caracteres")
    .max(100, "La contraseña no puede exceder los 100 caracteres")
    .regex(/[A-Za-z]/, "La contraseña debe contener al menos una letra")
    .regex(/[0-9]/, "La contraseña debe contener al menos un número"),

  rol: z
    .string({ message: "El rol es obligatorio" })
    .trim()
    .min(1, "El rol no puede estar vacío")
    .transform((val) => val.toUpperCase()),
});

export type RegisterInput = z.infer<typeof registerSchema>;
