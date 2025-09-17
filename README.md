# MTG Web Project

Laravel 12 + Vite basiertes Projekt zum Suchen von MTG‑Karten und Bauen von Decks. Der Code liegt im Ordner `mtg-backend/` und wird als klassische Laravel‑App mit Vite‑Assets betrieben.

## Anforderungen (Linux)

- `PHP >= 8.2` inkl. üblicher Extensions: `openssl`, `pdo`, `mbstring`, `tokenizer`, `xml`, `ctype`, `json`, `fileinfo` (und für SQLite: `pdo_sqlite`, `sqlite3`)
- `Composer >= 2.6`
- `Node.js >= 18` und `npm >= 9` (für Vite 7)
- `SQLite 3`
- Optional: `git`

Beispielinstallation (Ubuntu/Debian):

```bash
sudo apt update
sudo apt install -y php php-cli php-mbstring php-xml php-curl php-sqlite3 php-zip unzip sqlite3 git

# Node.js (empfohlen via nvm, um eine aktuelle Version zu bekommen)
curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.39.7/install.sh | bash
source ~/.bashrc
nvm install --lts # Node 18+ (LTS) installieren
```

## Projekt aufsetzen (erstes Setup)

```bash
cd "~/home/l/Documents/Uni~/MTG Web Project/mtg-backend"

# 1) Abhängigkeiten installieren
composer install
npm install

# 2) .env anlegen und App Key setzen
cp .env.example .env
php artisan key:generate

# 3) Datenbank (SQLite) vorbereiten
mkdir -p database
test -f database/database.sqlite || touch database/database.sqlite

# 4) Migrations ausführen
php artisan migrate
```

Hinweise:
- Standardmäßig ist `DB_CONNECTION=sqlite` konfiguriert (`config/database.php`). Für MySQL/PGSQL einfach `.env` anpassen.
- Absenderadresse für Mails kommt aus `MAIL_FROM_ADDRESS` (siehe `.env`).

## Entwicklung starten

Variante A – zwei Terminals (empfohlen für klares Logging):

```bash
# Terminal 1 – PHP Server
cd "/home/l/Documents/Uni/MTG Web Project/mtg-backend"
php artisan serve

# Terminal 2 – Vite (Assets mit HMR)
cd "/home/l/Documents/Uni/MTG Web Project/mtg-backend"
npm run dev
```

Die App ist danach i. d. R. unter `http://127.0.0.1:8000` erreichbar.

Variante B – alles in einem Prozess (Composer Script):

```bash
cd "/home/l/Documents/Uni/MTG Web Project/mtg-backend"
composer run dev
```

Dieses Skript startet parallel: PHP‑Server, Queue Listener, Log‑Tail (`pail`) und Vite (`npm run dev`).

## Build (Production‑Assets)

```bash
cd "/home/l/Documents/Uni/MTG Web Project/mtg-backend"
npm run build
```

Die gebauten Assets landen in `public/build`. Für einen Production‑Betrieb zusätzlich in `.env` setzen:

```bash
APP_ENV=production
APP_DEBUG=false
APP_URL="https://deine-domain.tld"
```

Optional Caches setzen/leeren:

```bash
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
```

## Nützliche Befehle

```bash
# Tests ausführen
php artisan test

# Datenbank migrieren/rollen
php artisan migrate
php artisan migrate:rollback

# Nutzerverwaltung, Tinker, etc.
php artisan tinker
```

## Troubleshooting

- Node‑Version zu alt: Bitte Node 18+ verwenden (z. B. via `nvm`).
- Port 8000 belegt: `php artisan serve --port=8001` verwenden.
- Datei‑/Ordnerrechte: Stellen Sie sicher, dass `storage/` und `bootstrap/cache/` beschreibbar sind.

```bash
cd "/home/l/Documents/Uni/MTG Web Project/mtg-backend"
chmod -R 775 storage bootstrap/cache
# Ggf. Besitz anpassen (USER ersetzen)
chown -R $USER:$USER storage bootstrap/cache
```

- SQLite fehlt oder schreibgeschützt: Datei `database/database.sqlite` erstellen und Schreibrechte prüfen.

---

Bei Fragen: siehe `mtg-backend/README.md` (Laravel Standardhinweise) oder die Laravel‑Dokumentation.
