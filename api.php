<?php
// API REST - Gestione Collezione Videogiochi
// Kai - TPSIT Web Service

require_once 'db.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$path   = isset($_GET['action']) ? $_GET['action'] : '';
$id     = isset($_GET['id']) ? (int)$_GET['id'] : null;

// Routing
switch ($method) {

    // ── GET ──────────────────────────────────────────────────────────────────
    case 'GET':
        if ($id) {
            // GET /api.php?action=giochi&id=5  →  dettaglio singolo gioco
            $stmt = $pdo->prepare('SELECT * FROM giochi WHERE id = :id');
            $stmt->execute(['id' => $id]);
            $gioco = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($gioco) {
                risposta(200, $gioco);
            } else {
                risposta(404, ['errore' => 'Gioco non trovato']);
            }
        } elseif (isset($_GET['piattaforma'])) {
            // GET /api.php?action=giochi&piattaforma=PS4
            $stmt = $pdo->prepare('SELECT * FROM giochi WHERE piattaforma = :p ORDER BY titolo');
            $stmt->execute(['p' => $_GET['piattaforma']]);
            risposta(200, $stmt->fetchAll(PDO::FETCH_ASSOC));
        } elseif (isset($_GET['genere'])) {
            // GET /api.php?action=giochi&genere=RPG
            $stmt = $pdo->prepare('SELECT * FROM giochi WHERE genere = :g ORDER BY titolo');
            $stmt->execute(['g' => $_GET['genere']]);
            risposta(200, $stmt->fetchAll(PDO::FETCH_ASSOC));
        } elseif (isset($_GET['cerca'])) {
            // GET /api.php?action=giochi&cerca=zelda
            $like = '%' . $_GET['cerca'] . '%';
            $stmt = $pdo->prepare('SELECT * FROM giochi WHERE titolo LIKE :t OR sviluppatore LIKE :t ORDER BY titolo');
            $stmt->execute(['t' => $like]);
            risposta(200, $stmt->fetchAll(PDO::FETCH_ASSOC));
        } else {
            // GET /api.php?action=giochi  →  tutti i giochi
            $stmt = $pdo->query('SELECT * FROM giochi ORDER BY titolo');
            risposta(200, $stmt->fetchAll(PDO::FETCH_ASSOC));
        }
        break;

    // ── POST ─────────────────────────────────────────────────────────────────
    case 'POST':
        $dati = json_decode(file_get_contents('php://input'), true);

        if (!isset($dati['titolo'], $dati['piattaforma'], $dati['genere'])) {
            risposta(400, ['errore' => 'Campi obbligatori: titolo, piattaforma, genere']);
            break;
        }

        $stmt = $pdo->prepare(
            'INSERT INTO giochi (titolo, piattaforma, genere, sviluppatore, anno_uscita, voto)
             VALUES (:titolo, :piattaforma, :genere, :sviluppatore, :anno_uscita, :voto)'
        );
        $stmt->execute([
            'titolo'        => $dati['titolo'],
            'piattaforma'   => $dati['piattaforma'],
            'genere'        => $dati['genere'],
            'sviluppatore'  => $dati['sviluppatore']  ?? null,
            'anno_uscita'   => $dati['anno_uscita']   ?? null,
            'voto'          => $dati['voto']           ?? null,
        ]);
        risposta(201, ['messaggio' => 'Gioco aggiunto', 'id' => $pdo->lastInsertId()]);
        break;

    // ── PUT ──────────────────────────────────────────────────────────────────
    case 'PUT':
        if (!$id) {
            risposta(400, ['errore' => 'ID obbligatorio per la modifica']);
            break;
        }
        $dati = json_decode(file_get_contents('php://input'), true);

        // Costruisce la query dinamicamente con solo i campi inviati
        $campi   = ['titolo', 'piattaforma', 'genere', 'sviluppatore', 'anno_uscita', 'voto'];
        $set     = [];
        $params  = ['id' => $id];

        foreach ($campi as $campo) {
            if (array_key_exists($campo, $dati)) {
                $set[]          = "$campo = :$campo";
                $params[$campo] = $dati[$campo];
            }
        }

        if (empty($set)) {
            risposta(400, ['errore' => 'Nessun campo da aggiornare']);
            break;
        }

        $stmt = $pdo->prepare('UPDATE giochi SET ' . implode(', ', $set) . ' WHERE id = :id');
        $stmt->execute($params);

        if ($stmt->rowCount() === 0) {
            risposta(404, ['errore' => 'Gioco non trovato']);
        } else {
            risposta(200, ['messaggio' => 'Gioco aggiornato']);
        }
        break;

    // ── DELETE ───────────────────────────────────────────────────────────────
    case 'DELETE':
        if (!$id) {
            risposta(400, ['errore' => 'ID obbligatorio per l\'eliminazione']);
            break;
        }
        $stmt = $pdo->prepare('DELETE FROM giochi WHERE id = :id');
        $stmt->execute(['id' => $id]);

        if ($stmt->rowCount() === 0) {
            risposta(404, ['errore' => 'Gioco non trovato']);
        } else {
            risposta(200, ['messaggio' => 'Gioco eliminato']);
        }
        break;

    default:
        risposta(405, ['errore' => 'Metodo non supportato']);
}

// ── Helper ────────────────────────────────────────────────────────────────────
function risposta(int $codice, $dati): void
{
    http_response_code($codice);
    echo json_encode($dati, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}