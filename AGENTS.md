# AGENTS.md: A Nicer Way

Ergänzt `~/www/agents/AGENTS.md` und geht bei Widerspruch vor.

## Git

- Arbeit auf einem eigenen Branch von `origin/develop`, Merge nach **`develop`** (Merge-Commit).
- Nach `master` nur nach ausdrücklicher Freigabe von Oliver.
- Kein Pushen und Mergen ohne Oliver (`cpw`, `cpm`).
- `.idea/` und `.junie/plans/` nicht committen.

## Stack

Laravel 12, Inertia 2, Vue 3 mit TypeScript, Pinia, Vite, Tailwind 3.4 und shadcn-vue (`resources/js/components/ui`). Datenbank lokal MariaDB (`a-nicer-way`), Tests laufen auf SQLite im Speicher. Reverb für die Fernsteuerung: einmal `php artisan reverb:install`, dann `composer dev` (startet auch Reverb). Details: `README.md`.

## Vor jedem Commit

```bash
npm run build   # zuerst: die Seiten-Tests brauchen public/build
php artisan test
npm run test:js   # Vitest (Stores, Countdown, Panels)
npm run types     # vue-tsc (Typencheck)
npm run lint
npm run format:check
```

Oberflächen zusätzlich im Browser prüfen (`/journeys/demo`). Tests allein reichen nicht.

## Konventionen

- Neuer Code der Präsentation: Komponenten in `resources/js/components/presentation`, Zustand im Pinia-Store `resources/js/stores/presentation.ts`, Typen in `resources/js/types/presentation.ts`.
- Editor (eingeloggt, `/editor/journeys`): Controller in `app/Http/Controllers/Editor`, Rechte über `JourneyPolicy`/`BoardPolicy` (nur Besitzer), Seiten `resources/js/pages/Journeys`, Bausteine in `resources/js/components/editor`.
- Bilder der Parallax-Ebenen liegen auf der privaten Disk (`BoardAsset::DISK`) und werden nur über `AssetController` ausgeliefert (Veröffentlichung/Besitzer prüfen, `Content-Security-Policy: sandbox`, `nosniff`). Nie über `/storage` ausliefern: SVGs können Skripte enthalten.
- Verschiebbare Panels (Boards, Notizen, Konsole, Timer) bauen auf `FloatingPanel.vue` auf; welche offen sind, steht im Store (`panels`, `togglePanel`). Meldungen für die Konsole über `useConsoleStore().log(...)`. Frontend-Logik mit Vitest testen (`*.test.ts` neben dem Code).
- Fernsteuerung: Kopplung über zwei Zufalls-Tokens (`App\Support\RemoteSession`, Cache, 12 h). Der Fernsteuer-Token benennt die öffentlichen Reverb-Kanäle `remote.{token}.commands` und `remote.{token}.state`; nur der Presenter-Token (nur als Hash gespeichert) darf Zustand veröffentlichen oder die Kopplung beenden. Befehle laufen über `RemoteController` (validiert, gedrosselt) und `RemoteCommand`/`RemoteState` (`ShouldBroadcastNow`), im Frontend über `stores/remote.ts` und `pages/Remote/Show.vue`. Neue Befehle: Whitelist in `RemoteController::command`, `RemoteAction` und `handleCommand`. Die Figur wird über `character-select` (Wert = Figur-Id, nur bekannte Ids) und `character-pose` (Feld `pose`) gesteuert; ihr Zustand steht in `RemoteState` (`characters`, `character_id`, `character_pose`).
- Der Timer gehört in den Store (`timer*` in `stores/presentation.ts`), nicht in ein Panel, damit die Fernsteuerung ihn auch bei geschlossenem Panel steuert.
- Figur-Ebene: `Character`/`CharacterImage` (pro Journey, ein Bild je Pose `CharacterPose`), Bilder wie die Parallax-Bilder auf der privaten Disk und nur über `AssetController` (`/character-images/{id}`). Zustand (`characterId`, `characterPose`, `setPose`) im Presentation-Store, Darstellung in `CharacterLayer.vue` (zwischen mittlerer und vorderer Ebene), Steuerung in `CharacterPanel.vue`. Upload-Feld: `components/editor/ImageUpload.vue`.
- Hintergründe (`JourneyBackdrop`, journey-weit): Ebene `back` oder `front`, Tempo, Ausrichtung, Höhe in %, Wiederholung. Dateien wie die anderen Bilder (privat, nur über `AssetController`, `/backdrops/{id}`). Darstellung in `BoardScroller.vue` (hinter bzw. vor den Board-Ebenen), Verwaltung in `components/editor/BackdropEditor.vue`.
- Import der alten Website: `php artisan journey:import-legacy` (`App\Services\LegacyJourneyImporter`). Die Zuordnung von Figuren-Posen und Hintergründen steht als Konstanten im Importer. Der Ordner `old_web` gehört **nicht** ins Repo (18 MB, Bild- und Piktogramm-Lizenzen unklar).
- Icons mit `lucide-vue-next`, Buttons mit `@/components/ui/button`, Farben und Abstände über die Theme-Variablen (keine Farbliterale).
- Keine lizenzierten Dateien (zum Beispiel Font Awesome Pro) im Repo.
- Keine Passwörter oder Tokens in Dateien, Commits oder Logs.

## Bekannte Lücken

- Alles aus `anw-presentation` ist portiert.
