<?php
$path = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);

switch ($path) {
    case '/api/expedientes':
        require '../api/expedientes.php';
        break;
    default:
        http_response_code(404);
        echo json_encode(["error" => "Not found"]);
}

echo <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Webservice de IBISEC</title>
</head>
<body>
    <h1>Bienvenido al Webservice de Expedientes</h1>
    <p>Utiliza los endpoints en <code>/api/</code> para acceder a los datos.</p>
</body>
</html>
HTML;