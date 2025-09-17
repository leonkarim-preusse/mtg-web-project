# MTG Web Project

Laravel 12 + Vite basiertes Projekt zum Suchen von MTG‑Karten und Bauen von MTG-Decks. Der Code liegt im Ordner `mtg-backend/` und wird als klassische Laravel‑App mit Vite‑Assets betrieben.

## Anforderungen (Linux)

- `PHP >= 8.2` inkl. üblicher Extensions: `openssl`, `pdo`, `mbstring`, `tokenizer`, `xml`, `ctype`, `json`, `fileinfo` (und für SQLite: `pdo_sqlite`, `sqlite3`)
- `Composer >= 2.6`
- `Node.js >= 18` und `npm >= 9` (für Vite)
- `SQLite 3`

Beispielinstallation (Ubuntu/Debian):

```bash
sudo apt update
sudo apt install -y php php-cli php-mbstring php-xml php-curl php-sqlite3 php-zip unzip sqlite3 git

# Node.js (nvm für aktuelle Versionen)
curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.39.7/install.sh | bash
source ~/.bashrc
nvm install --lts 
```

## Projekt aufsetzen (erstes Setup)

```bash
cd "~/mtg-backend"

# 1) Abhängigkeiten installieren
composer install
npm install

# 2) .env anlegen und App
cp .env.example .env
php artisan key:generate

# 3) Datenbank vorbereiten
mkdir -p database
test -f database/database.sqlite || touch database/database.sqlite

# 4) Migrations ausführen
php artisan migrate
```

Hinweise:
- Standardmäßig ist `DB_CONNECTION=sqlite` konfiguriert (`config/database.php`). Für MySQL/PGSQL einfach `.env` anpassen.
- Mail Adress konfigurierbar: `MAIL_FROM_ADDRESS` (siehe `.env`).

## Entwicklung starten

Variante A – zwei Terminals:

```bash
# Terminal 1 – PHP Server
cd "/home/l/Documents/Uni/MTG Web Project/mtg-backend"
php artisan serve

# Terminal 2 – Vite
cd "/home/l/Documents/Uni/MTG Web Project/mtg-backend"
npm run dev
```

Die App ist ohne weitere Konfigurationen unter `http://127.0.0.1:8000` erreichbar.

Variante B:

```bash
cd "/home/l/Documents/Uni/MTG Web Project/mtg-backend"
composer run dev
```

Startet parallel: PHP‑Server, Queue Listener, Log‑Tail (`pail`) und Vite (`npm run dev`).

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
- Port 8000 belegt: `php artisan serve --port=8001` (oder anderen Port) verwenden.
- Datei‑/Ordnerrechte: Schreibrechte für `storage/` und `bootstrap/cache/`

```bash
cd "/home/l/Documents/Uni/MTG Web Project/mtg-backend"
chmod -R 775 storage bootstrap/cache
# ownership issue
chown -R $USER:$USER storage bootstrap/cache
```

- SQLite fehlt oder schreibgeschützt: Datei `database/database.sqlite` erstellen und Schreibrechte prüfen.

---

Bei Fragen: siehe `mtg-backend/README.md` (Laravel Standardhinweise) oder die Laravel‑Dokumentation.
