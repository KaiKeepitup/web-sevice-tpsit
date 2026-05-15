<?php
// db.php - Connessione database
// Modifica host/dbname/user/password in base alla tua configurazione XAMPP

$host     = 'localhost';
$dbname   = 'videogame_collection';
$username = 'root';
$password = '';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['errore' => 'Connessione al database fallita: ' . $e->getMessage()]);
    exit;
}