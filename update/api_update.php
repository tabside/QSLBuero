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
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/../dbConnect.php';

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
$apiUrl = 'https://new.uska.ch/wp-json/uska/v1/qsl-export';

$ctx = stream_context_create([
    'http' => [
        'method'  => 'GET',
        'header'  => "X-QSL-Token: {$token}\r\nAccept: application/json\r\n",
        'timeout' => 30,
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

// ---------- Datenbankupdate ----------
$mysqli->set_charset('utf8mb4');
$mysqli->query("TRUNCATE TABLE qsl");

$sql = "
    INSERT INTO qsl (
        Kontakte, callsign, Mitgliedschaft, Vorname, Nachname,
        Strasse, Postfach, PLZ, Ort, Land,
        Handy, Email, Eintritt, Austritt, Geschlecht, Geburtsdatum, Sprache,
        via, noqsl,
        vName, vStrasse, vPostfach, vOrt, vLand, vBemerk
    ) VALUES (?, ?, '', ?, ?, ?, ?, ?, ?, ?, '', ?, '', '', '', '', ?, 0, ?, ?, ?, ?, ?, ?, ?)
";
// Platzhalter: Kontakte, callsign, Vorname, Nachname, Strasse, Postfach,
//              PLZ, Ort, Land, Email, Sprache, noqsl,
//              vName, vStrasse, vPostfach, vOrt, vLand, vBemerk = 18

$stmt = $mysqli->prepare($sql);
if (!$stmt) {
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
    $strasse = trim(($addr['street'] ?? '') . ($addr['addition'] ? "\n" . $addr['addition'] : ''));
    $postfach = $addr['po_box'] ?? '';
    $plz     = $addr['zip']     ?? '';
    $ort     = $addr['city']    ?? '';
    $land    = $addr['country'] ?? '';

    // QSL-Weiterleitungsadresse (qsl_address)
    if (!empty($m['qsl_address'])) {
        $qa       = $m['qsl_address'];
        $vName    = trim(($qa['first_name'] ?? $vorname) . ' ' . ($qa['last_name'] ?? $nachname));
        $vStrasse = trim(($qa['street'] ?? '') . ($qa['addition'] ? "\n" . $qa['addition'] : ''));
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
        'sssssssssssisssssss',
        $kontakte, $callsign, $vorname, $nachname,
        $strasse, $postfach, $plz, $ort, $land,
        $email, $sprache,
        $noqsl,
        $vName, $vStrasse, $vPostfach, $vOrt, $vLand, $vBemerk
    );

    if ($stmt->execute()) {
        $inserted++;
    } else {
        $errors[] = "Callsign {$callsign}: " . $stmt->error;
    }
}

$stmt->close();

// ---------- Ergebnis ----------
echo "DB Update erfolgreich!\n";
echo "API-Stand: {$generatedAt}\n";
echo "Mitglieder von API: {$apiCount}\n";
echo "Importiert: {$inserted}\n";

if (!empty($errors)) {
    echo "\nFehler bei " . count($errors) . " Einträgen:\n";
    foreach ($errors as $e) {
        echo "  - {$e}\n";
    }
}
