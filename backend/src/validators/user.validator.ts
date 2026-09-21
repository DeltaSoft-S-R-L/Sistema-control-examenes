import { z } from "zod";

/**
 * Esquema para validar los parámetros de la URL (:id)
 */
export const userParamsSchema = z.object({
  id: z
    .string({ message: "El parámetro id es obligatorio" })
    .trim()
    .regex(/^\d+$/, "El parámetro id debe ser un número entero positivo"),
});

export type UserParamsInput = z.infer<typeof userParamsSchema>;

/**
 * Esquema para validar el cuerpo de actualización parcial de usuario
 */
export const updateUserSchema = z
  .object({
    nombre: z
      .string()
      .trim()
      .min(2, "El nombre debe tener al menos 2 caracteres")
      .max(100, "El nombre no puede exceder los 100 caracteres")
      .optional(),

    apellido: z
      .string()
      .trim()
      .min(2, "El apellido debe tener al menos 2 caracteres")
      .max(100, "El apellido no puede exceder los 100 caracteres")
      .optional(),

    correo: z
      .string()
      .trim()
      .email("El formato del correo electrónico no es válido")
      .max(150, "El correo no puede exceder los 150 caracteres")
      .toLowerCase()
      .optional(),

    username: z
      .string()
      .trim()
      .min(3, "El nombre de usuario debe tener al menos 3 caracteres")
      .max(50, "El nombre de usuario no puede exceder los 50 caracteres")
      .regex(
        /^[a-zA-Z0-9_]+$/,
        "El nombre de usuario solo puede contener caracteres alfanuméricos y guiones bajos"
      )
      .optional(),

    rol: z
      .string()
      .trim()
      .min(1, "El rol no puede estar vacío")
      .transform((val) => val.toUpperCase())
      .optional(),

    estado: z
      .string()
      .trim()
      .transform((val) => val.toUpperCase())
      .refine(
        (val) => ["ACTIVO", "REVOCADO", "INACTIVO", "BLOQUEADO"].includes(val),
        { message: "El estado proporcionado no es válido" }
      )
      .transform((val) => {
        // En PostgreSQL (ck_usuario_estado), los estados permitidos son 'ACTIVO' o 'REVOCADO'.
        // Mapeamos estados de baja/bloqueo a 'REVOCADO' para garantizar integridad de BD.
        if (val === "INACTIVO" || val === "BLOQUEADO") return "REVOCADO";
        return val;
      })
      .optional(),
  })
  .refine(
    (data) => Object.values(data).some((val) => val !== undefined),
    {
      message: "Debe proporcionar al menos un campo para actualizar",
    }
  );

export type UpdateUserInput = z.infer<typeof updateUserSchema>;
