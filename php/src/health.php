<?php
header('Content-Type: application/json');

try {
    // Read the password from the file path specified by the environment variable
    $password = trim(file_get_contents(getenv('DB_PASSWORD_FILE')));

    $pdo = new PDO(
        "mysql:host=" . getenv('DB_HOST') . ";dbname=" . getenv('DB_DATABASE'),
        getenv('DB_USER'),
        $password
    );
    // Set PDO to throw exceptions on error
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo json_encode(['status' => 'healthy']);

} catch (Exception $e) {
    // In case of an error, send a 503 Service Unavailable header
    header('HTTP/1.1 503 Service Unavailable');
    echo json_encode(['status' => 'unhealthy', 'error' => $e->getMessage()]);
}