<?php
function getConnection() {
    $host = '10.215.26.87';
    $port = '5432';
    $dbname = 'IBISEC';
    $user = 'infraestructures';
    $password = 'infraestructures';

    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";

    try {
        $pdo = new PDO($dsn, $user, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        return $pdo;
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["error" => "Error de conexión a la BD: " . $e->getMessage()]);
        exit;
    }
}
