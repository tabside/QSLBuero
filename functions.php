<?php
/**
 * Gemeinsame Hilfsfunktionen für index.php und detail.php.
 */

/**
 * HTML-Ausgabekodierung für alle Werte aus der Datenbank oder aus Requests.
 */
function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * JSON für ein HTML-Attribut (value='...'): < > & ' " werden als \uXXXX kodiert,
 * damit der Wert weder das Attribut noch ein Script-Tag verlassen kann.
 */
function json_attr($value): string
{
    return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
}

/**
 * Rufzeichen aus einem Request-Parameter: nur A-Z, 0-9, / und -, 3 bis 12 Zeichen.
 * Liefert das Rufzeichen in Grossbuchstaben oder null, wenn der Wert nicht passt.
 */
function sanitize_callsign($value): ?string
{
    if (!is_string($value)) {
        return null;
    }
    $value = trim($value);
    if (!preg_match('/^[A-Z0-9\/\-]{3,12}$/i', $value)) {
        return null;
    }
    return strtoupper($value);
}

/**
 * Inhalt der Zählerdatei counter/<call>.txt, nur innerhalb von counter/ (kein Pfad-Ausbruch).
 * Liefert "0", wenn es keine Datei gibt.
 */
function read_counter(string $call): string
{
    $dir = realpath(__DIR__ . '/counter');
    if ($dir === false) {
        return '0';
    }
    $file = $dir . DIRECTORY_SEPARATOR . basename($call) . '.txt';
    $real = realpath($file);
    if ($real === false || strpos($real, $dir . DIRECTORY_SEPARATOR) !== 0 || !is_file($real)) {
        return '0';
    }
    $content = file_get_contents($real);
    return $content === false ? '0' : trim($content);
}
