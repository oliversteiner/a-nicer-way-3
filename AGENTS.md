# AGENTS.md: A Nicer Way

Ergänzt `~/www/agents/AGENTS.md` und geht bei Widerspruch vor.

## Git

- Arbeit auf einem eigenen Branch von `origin/develop`, Merge nach **`develop`** (Merge-Commit).
- Nach `master` nur nach ausdrücklicher Freigabe von Oliver.
- Kein Pushen und Mergen ohne Oliver (`cpw`, `cpm`).
- `.idea/` und `.junie/plans/` nicht committen.

## Stack

Laravel 12, Inertia 2, Vue 3 mit TypeScript, Pinia, Vite, Tailwind 3.4 und shadcn-vue (`resources/js/components/ui`). Datenbank lokal MariaDB (`a-nicer-way`), Tests laufen auf SQLite im Speicher. Details: `README.md`.

## Vor jedem Commit

```bash
npm run build   # zuerst: die Seiten-Tests brauchen public/build
php artisan test
npm run lint
npm run format:check
```

Oberflächen zusätzlich im Browser prüfen (`/journeys/demo`). Tests allein reichen nicht.

## Konventionen

- Neuer Code der Präsentation: Komponenten in `resources/js/components/presentation`, Zustand im Pinia-Store `resources/js/stores/presentation.ts`, Typen in `resources/js/types/presentation.ts`.
- Editor (eingeloggt, `/editor/journeys`): Controller in `app/Http/Controllers/Editor`, Rechte über `JourneyPolicy`/`BoardPolicy` (nur Besitzer), Seiten `resources/js/pages/Journeys`, Bausteine in `resources/js/components/editor`.
- Bilder der Parallax-Ebenen liegen auf der privaten Disk (`BoardAsset::DISK`) und werden nur über `AssetController` ausgeliefert (Veröffentlichung/Besitzer prüfen, `Content-Security-Policy: sandbox`, `nosniff`). Nie über `/storage` ausliefern: SVGs können Skripte enthalten.
- Icons mit `lucide-vue-next`, Buttons mit `@/components/ui/button`, Farben und Abstände über die Theme-Variablen (keine Farbliterale).
- Keine lizenzierten Dateien (zum Beispiel Font Awesome Pro) im Repo.
- Keine Passwörter oder Tokens in Dateien, Commits oder Logs.

## Bekannte Lücken

- `npx vue-tsc --noEmit` scheitert am Starter-Kit (`vue/tsx` in `tsconfig.json`).
- Nicht portiert aus `anw-presentation`: Board-Liste, Console, Timer, Figur-Ebene, Fernsteuerung.
