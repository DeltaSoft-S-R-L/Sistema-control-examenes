
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bienvenida al Sistema de Control de Exámenes</title>
</head>

<body style="font-family: Arial, sans-serif; padding: 24px; color: #333; background-color: #f5f6fa;">

    <div style="max-width: 600px; margin: auto; background: #fff; padding: 30px; border-radius: 8px;">

        <h2 style="color: #2563eb;">
            Bienvenido al Sistema de Control de Exámenes
        </h2>

        <p>
            Estimado/a {{ $usuario->nombre }} {{ $usuario->apellido }}:
        </p>

        <p>
            DeltaSoft S.R.L. le informa que su cuenta estudiantil
            ha sido creada correctamente.
        </p>

        <h3>Datos para iniciar sesión</h3>

        <div style="background: #f1f5f9; padding: 15px; border-radius: 6px;">

            <p>
                <strong>Usuario (Código SIS):</strong>
                {{ $usuario->username }}
            </p>

            <p>
                <strong>Contraseña inicial:</strong>
                Su número de carnet de identidad (CI).
            </p>

        </div>

        <p>
            Por seguridad, el número de su carnet de identidad
            no se muestra en este correo.
        </p>

        <p>
            Para ingresar al sistema, utilice su código SIS
            como usuario y su CI como contraseña inicial.
        </p>

        <p>
            Una vez dentro del panel estudiantil, encontrará
            la opción <strong>Cambiar contraseña</strong>.
            Este cambio es opcional.
        </p>

        <p>
            Le recomendamos cambiar su contraseña por una
            contraseña personal para proteger su cuenta.
        </p>

        <p>
            Si usted no reconoce este registro, comuníquese
            con el administrador del sistema.
        </p>

        <hr style="border: 0; border-top: 1px solid #ddd; margin-top: 25px;">

        <p style="font-size: 13px; color: #666;">
            Atentamente,<br>
            <strong>DeltaSoft S.R.L.</strong><br>
            Sistema de Control de Exámenes
        </p>

    </div>

</body>
</html>
