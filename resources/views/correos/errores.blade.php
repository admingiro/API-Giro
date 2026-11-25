<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error</title>
</head>
<body>
    <p>
        <b>Hola, buen día</b>
    </p>

    <p>El dia {{ $fecha }}, el sistema detectó el proceso de <b>{{ $proceso }}</b> para procesar datos del empleado, pero ocurrió un <b style="color: red;">{{ $problema }}</b>,
       el cual impidio concluir correctamente el proceso.
    </p>
    <p><b>El {{ $problema }} es:</b> <i>{{ $msjError }}</i></p>

    <p>Por favor, no responda a este mensaje, ha sido enviado de forma automática. Si desea ponerse en contacto con nosotros
    para comentarnos alguna incidencia o mejora de este servicio, por favor, escríbanos a soporte@giro.com.mx.</p>

    <p>Saludos...</p>
</body>
</html>