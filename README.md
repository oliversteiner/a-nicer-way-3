# A Nicer Way

A user journey presentation app with a smartphone simulator.

A journey is told as a row of boards (two days, hour by hour). Scrolling sideways moves through the boards with a three-layer parallax background. A status bar shows day, time, place, mood and day/night, and a phone simulator shows what the app does at that point of the story.

## Stack

- Laravel 12, PHP 8.2+, MariaDB / MySQL
- Inertia 2, Vue 3 (`<script setup>`, TypeScript), Pinia, Laravel Reverb + Echo (remote control)
- Vite 6, Tailwind CSS 3.4, shadcn-vue components, lucide icons, VueUse
- Pest / PHPUnit for tests

## Setup

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan reverb:install   # adds the Reverb keys to .env (once)
```

Create the database named in `.env` (default `a-nicer-way`), then:

```bash
php artisan migrate --seed
```

The seeder adds a demo journey with 11 boards.

## Run

```bash
composer dev      # server, queue, logs, Vite and Reverb
# or
php artisan serve
npm run dev
php artisan reverb:start   # only needed for the remote control
```

Open `/journeys/demo`. Use the left and right arrow keys, the arrow buttons or the dots to move between boards.

Log in and open `/editor/journeys` (menu entry «Journeys») to create journeys, add, edit, reorder and delete boards, and publish a journey. Only the owner can edit a journey; drafts are visible to the owner only.

Characters (figures that stand in the scene) are managed on the same page: each has a name and one image per pose (standing, walking, break, greeting); a pose without an image shows the standing image, a character without images a labelled placeholder. In the presentation the «Figur» panel picks the character and its action; a greeting returns to standing after a moment.

Each board takes images for the three parallax layers (PNG, JPG, WebP or SVG, up to 5 MB). They (and the character images) are stored on the private `local` disk and served through `/assets/{id}` and `/character-images/{id}`, only for published journeys (or to the owner) and with a sandboxing `Content-Security-Policy`, so a script inside an uploaded SVG cannot run.

Remote control: the «Fernsteuerung» panel shows a QR code and a link. Open it on a phone to switch boards (back, next, jump to a board), start, pause and reset the timer, pick the character and its action (standing, walking, break, greeting) and read the notes of the current board. The presentation publishes its state through Reverb; the phone sends commands to the server, which checks them and broadcasts them to the presentation. A pairing is a random token that expires after 12 hours; «Neu koppeln» ends the old one. Reverb needs a long-running process (`php artisan reverb:start`) and, behind a proxy, a secure WebSocket (`wss`) route; see the Reverb docs.

The toolbar in the status bar opens movable panels: board list (jump to a board), notes of the current board, phone simulator, console (log of board changes and timer events) and a countdown timer for the talk.

## Checks

```bash
npm run build         # page tests need the Vite manifest in public/build
php artisan test      # tests run on in-memory SQLite
npm run test:js       # Vitest: stores, countdown, panels
npm run types         # vue-tsc type check
npm run lint
npm run format:check
```

## Data model

- `journeys`: title, unique slug, description, `published_at` (drafts are visible to the owner only)
- `boards`: ordered by `position`; day, time, location, night flag, mood, `simulator_state` (JSON, e.g. `{"screen": "Fahrplan"}`)
- `board_assets`: images for the parallax layers `back`, `middle` and `front`
- `characters` and `character_images`: figures with one image per pose (`idle`, `go`, `pause`, `greet`)

## History

- v3: Laravel + Vue 3 + Inertia (this repository)
- v2: TypeScript, Bootstrap 4, Express.js, Socket.io, PouchDB
- v1: jQuery, Bootstrap 3, Express.js, Socket.io, PouchDB

## License

See [LICENSE](LICENSE).
