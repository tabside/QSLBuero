-- Aktuelles Rufzeichen aus der USKA-API (current_callsign).
-- Einmalig mit einem DB-Benutzer mit ALTER-Recht ausführen (qsl_app hat keines).
-- Danach einmal "DB Update" machen, damit die Spalte befüllt wird.
ALTER TABLE qsl ADD COLUMN current_callsign VARCHAR(20) NOT NULL DEFAULT '';
