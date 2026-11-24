# A Nicer Way

A user journey presentation app with a smartphone simulator.

A journey is told as a row of boards (two days, hour by hour). Scrolling sideways moves through the boards with a three-layer parallax background. A status bar shows day, time, place, mood and day/night, and a phone simulator shows what the app does at that point of the story.

## Stack

- Laravel 12, PHP 8.2+, MariaDB / MySQL
- Inertia 2, Vue 3 (`<script setup>`, TypeScript), Pinia
- Vite 6, Tailwind CSS 3.4, shadcn-vue components, lucide icons, VueUse
- Pest / PHPUnit for tests

## Setup

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Create the database named in `.env` (default `a-nicer-way`), then:

```bash
php artisan migrate --seed
```

The seeder adds a demo journey with 11 boards.

## Run

```bash
composer dev      # server, queue, logs and Vite
# or
php artisan serve
npm run dev
```

Open `/journeys/demo`. Use the left and right arrow keys, the arrow buttons or the dots to move between boards.

Log in and open `/editor/journeys` (menu entry «Journeys») to create journeys, add, edit, reorder and delete boards, and publish a journey. Only the owner can edit a journey; drafts are visible to the owner only.

Each board takes images for the three parallax layers (PNG, JPG, WebP or SVG, up to 5 MB). They are stored on the private `local` disk and served through `/assets/{id}`, only for published journeys (or to the owner) and with a sandboxing `Content-Security-Policy`, so a script inside an uploaded SVG cannot run.

## Checks

```bash
npm run build         # page tests need the Vite manifest in public/build
php artisan test      # tests run on in-memory SQLite
npm run lint
npm run format:check
npm run build
```

## Data model

- `journeys`: title, unique slug, description, `published_at` (drafts are visible to the owner only)
- `boards`: ordered by `position`; day, time, location, night flag, mood, `simulator_state` (JSON, e.g. `{"screen": "Fahrplan"}`)
- `board_assets`: images for the parallax layers `back`, `middle` and `front`

## History

- v3: Laravel + Vue 3 + Inertia (this repository)
- v2: TypeScript, Bootstrap 4, Express.js, Socket.io, PouchDB
- v1: jQuery, Bootstrap 3, Express.js, Socket.io, PouchDB

## Not ported yet

Board list and console panels, the timer, the character layer and the remote control from the earlier Vue 2 version (`anw-presentation`).

## License

See [LICENSE](LICENSE).
