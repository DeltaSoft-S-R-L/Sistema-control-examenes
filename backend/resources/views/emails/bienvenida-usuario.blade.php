
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Bienvenido a DeltaSoft</title>
</head>
<body style="font-family: Arial, sans-serif; padding: 24px; color: #333;">

    <h2>Bienvenido al Sistema de Control de Exámenes</h2>

    <p>Estimado/a {{ $usuario->nombre }} {{ $usuario->apellido }}:</p>

    <p>
        DeltaSoft S.R.L. le informa que su cuenta
        ha sido registrada correctamente.
    </p>

    <h3>Datos de su cuenta</h3>

    <p><strong>Usuario:</strong> {{ $usuario->username }}</p>
    <p><strong>Correo:</strong> {{ $usuario->correo }}</p>
    <p><strong>Rol:</strong> {{ $usuario->rol?->nombre ?? 'Sin asignar' }}</p>

    @if ($enlaceConfiguracion)
        <p>
            Para acceder al sistema, primero debe configurar
            su contraseña personal.
        </p>

        <p style="margin: 24px 0;">
            <a href="{{ $enlaceConfiguracion }}"
               style="background-color: #2563eb;
                      color: #ffffff;
                      padding: 12px 20px;
                      text-decoration: none;
                      border-radius: 6px;
                      display: inline-block;">
                Configurar mi contraseña
            </a>
        </p>

        <p>
            Este enlace es válido durante <strong>30 minutos</strong>
            y solo puede utilizarse una vez.
        </p>

        <p>
            Si el botón no funciona, copie el siguiente enlace
            y péguelo en su navegador:
        </p>

        <p>
            <a href="{{ $enlaceConfiguracion }}"
               style="color: #2563eb; word-break: break-all;">
                {{ $enlaceConfiguracion }}
            </a>
        </p>

        <p>
            Si usted no esperaba este correo, comuníquese
            con el administrador del sistema.
        </p>
    @else
        <p>
            Para configurar su acceso, comuníquese con el
            administrador del sistema.
        </p>
    @endif

    <p>
        Por seguridad, no enviamos contraseñas por correo electrónico.
    </p>

    <hr>

    <p>
        Atentamente,<br>
        <strong>DeltaSoft S.R.L.</strong><br>
        Sistema de Control de Exámenes
    </p>

</body>
</html>
