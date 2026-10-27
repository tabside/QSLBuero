<?php
/**
 * QSL Datenbank Update via USKA API
 *
 * Ruft die Mitgliederliste direkt von der USKA REST-API ab und
 * aktualisiert die lokale qsl-Tabelle vollständig (TRUNCATE + INSERT).
 *
 * Token in update/config.php als Konstante QSL_TOKEN definieren:
 *   define('QSL_TOKEN', 'dein-token-hier');
 *
 * Alternativ als Umgebungsvariable: QSL_TOKEN=... php api_update.php
 *
 * Aufruf nur über die Kommandozeile (php update/api_update.php) oder per
 * HTTP POST mit dem Header X-Update-Token, der dem UPDATE_TOKEN aus
 * dbConnect.php entsprechen muss (der "DB Update"-Button in index.php
 * schickt ihn mit). Ohne UPDATE_TOKEN ist der Web-Aufruf gesperrt.
 */

ini_set('display_errors', '0');
error_reporting(E_ALL);

$isCli = (php_sapi_name() === 'cli');
if (!$isCli) {
    header('Content-Type: text/plain; charset=utf-8');
    header('Cache-Control: no-store');
}

// Die USKA-API bereitet die Daten seit Oktober 2026 in Echtzeit auf und
// braucht dafür ca. 80 Sekunden. Unter Windows (WAMP) zählt die Wartezeit
// auf die API bei max_execution_time mit, darum das Limit hier hochsetzen.
set_time_limit(300);

require __DIR__ . '/../dbConnect.php';

// ---------- Zugriffsschutz ----------
if (!$isCli) {
    $expected = defined('UPDATE_TOKEN') ? (string)UPDATE_TOKEN : '';
    $presented = (string)($_SERVER['HTTP_X_UPDATE_TOKEN'] ?? '');
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        header('Allow: POST');
        die('Fehler: DB Update nur per POST (Button "DB Update") oder über die Kommandozeile.');
    }
    if ($expected === '' || strlen($expected) < 16) {
        http_response_code(403);
        die('Fehler: UPDATE_TOKEN in dbConnect.php nicht gesetzt (mindestens 16 Zeichen). Update nur über die Kommandozeile möglich.');
    }
    if ($presented === '' || !hash_equals($expected, $presented)) {
        http_response_code(403);
        die('Fehler: Zugriff verweigert (UPDATE_TOKEN falsch).');
    }
}

// ---------- Token laden ----------
$configFile = __DIR__ . '/config.php';
if (file_exists($configFile)) {
    require $configFile;
}
$token = defined('QSL_TOKEN') ? QSL_TOKEN : (getenv('QSL_TOKEN') ?: '');

if (empty($token)) {
    die('Fehler: QSL_TOKEN nicht konfiguriert. Bitte update/config.php anlegen (siehe config.php.example).');
}

// ---------- API abrufen ----------
$apiUrl = 'https://uska.ch/wp-json/uska/v1/qsl-export';

$ctx = stream_context_create([
    'http' => [
        'method'  => 'GET',
        'header'  => "X-QSL-Token: {$token}\r\nAccept: application/json\r\n",
        'timeout' => 240, // API-Antwort dauert ca. 80 Sekunden (Echtzeit-Aufbereitung)
    ],
    'ssl' => [
        'verify_peer'      => true,
        'verify_peer_name' => true,
    ],
]);

$raw = @file_get_contents($apiUrl, false, $ctx);
if ($raw === false) {
    die('Fehler: API nicht erreichbar (Verbindung fehlgeschlagen).');
}

// HTTP-Statuscode prüfen
$status = 0;
foreach ($http_response_header as $h) {
    if (preg_match('#HTTP/\S+ (\d+)#', $h, $m)) {
        $status = (int)$m[1];
    }
}
if ($status !== 200) {
    $err = json_decode($raw, true);
    $msg = $err['message'] ?? $raw;
    die("Fehler: API hat HTTP {$status} zurückgegeben – {$msg}");
}

$data = json_decode($raw, true);
if (!isset($data['members']) || !is_array($data['members'])) {
    die('Fehler: Ungültige API-Antwort (kein members-Array).');
}

$members    = $data['members'];
$apiCount   = $data['count'] ?? count($members);
$generatedAt = $data['generated_at'] ?? '?';

if (count($members) === 0) {
    die('Fehler: API hat keine Mitglieder geliefert – bestehende Daten bleiben unverändert.');
}

// ---------- Datenbankupdate ----------
// Alles in einer Transaktion: der alte Bestand wird erst ersetzt, wenn jeder
// Datensatz importiert ist. TRUNCATE würde in MySQL sofort committen, darum DELETE.
$mysqli->set_charset('utf8mb4');
try {
    if (!$mysqli->begin_transaction()) {
        die('Fehler: Transaktion konnte nicht gestartet werden.');
    }
    if (!$mysqli->query("DELETE FROM qsl")) {
        $mysqli->rollback();
        die('Fehler beim Leeren der Tabelle: ' . $mysqli->error);
    }
} catch (mysqli_sql_exception $e) { // PHP >= 8.1 wirft statt false zu liefern
    $mysqli->rollback();
    die('Fehler beim Leeren der Tabelle: ' . $e->getMessage());
}

$sql = "
    INSERT INTO qsl (
        Kontakte, callsign, Mitgliedschaft, Vorname, Nachname,
        Strasse, Postfach, PLZ, Ort, Land,
        Handy, Email, Eintritt, Austritt, Geschlecht, Geburtsdatum, Sprache,
        via, noqsl,
        vName, vStrasse, vPostfach, vOrt, vLand, vBemerk
    ) VALUES (?, ?, '', ?, ?, ?, ?, ?, ?, ?, '', ?, '', '', '', '', ?, ?, ?, ?, ?, ?, ?, ?, ?)
";
// Platzhalter: Kontakte, callsign, Vorname, Nachname, Strasse, Postfach,
//              PLZ, Ort, Land, Email, Sprache, via, noqsl,
//              vName, vStrasse, vPostfach, vOrt, vLand, vBemerk = 19

try {
    $stmt = $mysqli->prepare($sql);
} catch (mysqli_sql_exception $e) {
    $stmt = false;
}
if (!$stmt) {
    $mysqli->rollback();
    die('Fehler beim Vorbereiten des Statements: ' . $mysqli->error);
}

$inserted = 0;
$errors   = [];

foreach ($members as $m) {
    $vorname  = $m['first_name']  ?? '';
    $nachname = $m['last_name']   ?? '';
    $callsign = $m['callsign']    ?? '';

    // "Nachname, Vorname" als Kontakte-Bezeichnung (konsistent mit altem Format)
    $kontakte = trim($nachname . ', ' . $vorname);

    // E-Mail – Platzhalter "Unbekannt" leer lassen
    $email = ($m['email'] === 'Unbekannt') ? '' : ($m['email'] ?? '');

    // Weiterleitungs-Flag
    // Sprache (ISO-Code aus API, z.B. "de", "fr", "it")
    $sprache  = $m['language'] ?? '';

    $noqsl = ($m['no_forward'] ?? false) ? 1 : 0;

    // Hauptadresse (corr_address)
    $addr    = $m['corr_address'] ?? [];
    $strasse = trim(($addr['street'] ?? '') . (!empty($addr['addition']) ? "\n" . $addr['addition'] : ''));
    $postfach = $addr['po_box'] ?? '';
    $plz     = $addr['zip']     ?? '';
    $ort     = $addr['city']    ?? '';
    $land    = $addr['country'] ?? '';

    // QSL-Weiterleitungsadresse (qsl_address)
    // via = 1: Etikett geht an die QSL-Adresse statt an die Hauptadresse (index.php)
    $qa  = is_array($m['qsl_address'] ?? null) ? $m['qsl_address'] : [];
    $via = (trim($qa['street'] ?? '') !== '' || trim($qa['po_box'] ?? '') !== '' || trim($qa['city'] ?? '') !== '') ? 1 : 0;
    if ($via) {
        $vName    = trim(($qa['first_name'] ?? $vorname) . ' ' . ($qa['last_name'] ?? $nachname));
        $vStrasse = trim(($qa['street'] ?? '') . (!empty($qa['addition']) ? "\n" . $qa['addition'] : ''));
        $vPostfach = $qa['po_box']   ?? '';
        $vOrt     = trim(($qa['zip'] ?? '') . ' ' . ($qa['city'] ?? ''));
        $vLand    = $qa['country']   ?? '';
    } else {
        // Leer, aber NOT NULL-Felder brauchen mindestens einen Leerzeichen-Default
        $vName = ' '; $vStrasse = ''; $vPostfach = ''; $vOrt = ' '; $vLand = '';
    }

    // Bemerkungen zusammenführen
    $bemerkParts = array_filter([
        $m['remarks']        ?? '',
        $m['alt_qsl_note']   ?? '',
        $m['no_forward_note'] ?? '',
    ]);
    $vBemerk = implode("\n", $bemerkParts);

    $stmt->bind_param(
        'sssssssssssiissssss',
        $kontakte, $callsign, $vorname, $nachname,
        $strasse, $postfach, $plz, $ort, $land,
        $email, $sprache,
        $via, $noqsl,
        $vName, $vStrasse, $vPostfach, $vOrt, $vLand, $vBemerk
    );

    try {
        $ok = $stmt->execute();
    } catch (mysqli_sql_exception $e) { // PHP >= 8.1 wirft statt false zu liefern
        $ok = false;
    }
    if ($ok) {
        $inserted++;
    } else {
        $errors[] = "Callsign {$callsign}: " . $stmt->error;
    }
}

$stmt->close();

// ---------- Ergebnis ----------
if (!empty($errors)) {
    // Nichts übernehmen: der bisherige Bestand bleibt vollständig erhalten
    $mysqli->rollback();
    echo "DB Update abgebrochen – alter Bestand unverändert.\n";
    echo "API-Stand: {$generatedAt}\n";
    echo "Mitglieder von API: {$apiCount}\n";
    echo "Fehler bei " . count($errors) . " Einträgen:\n";
    foreach ($errors as $e) {
        echo "  - {$e}\n";
    }
    exit(1);
}

if (!$mysqli->commit()) {
    $mysqli->rollback();
    die('Fehler: Commit fehlgeschlagen – alter Bestand unverändert.');
}

echo "DB Update erfolgreich!\n";
echo "API-Stand: {$generatedAt}\n";
echo "Mitglieder von API: {$apiCount}\n";
echo "Importiert: {$inserted}\n";
