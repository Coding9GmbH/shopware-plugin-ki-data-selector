# Build & Release Anleitung

Dieses Plugin nutzt ein **Makefile** für automatisierte Builds und Releases.

## Quick Start

```bash
# Release ZIP erstellen
make release
```

Das ZIP wird unter `releases/Coding9KIDataSelector-{VERSION}.zip` abgelegt.

---

## Verfügbare Commands

### 🎯 Haupt-Commands

```bash
make release          # Erstellt fertiges Release-ZIP (empfohlen)
make build            # Baut das Plugin ohne aufzuräumen
make clean           # Entfernt Build-Artefakte
make help            # Zeigt alle verfügbaren Commands
```

### 📦 Build & Packaging

```bash
make release         # Kompletter Release-Build:
                     # 1. Bereinigt alte Builds
                     # 2. Erstellt releases/ Verzeichnis
                     # 3. Packt ZIP ohne macOS-Dateien
                     # 4. Verifiziert ZIP-Inhalt
                     # 5. Zeigt Dateiinfo

make build           # Nur ZIP erstellen (ohne cleanup)
make verify-zip      # ZIP auf __MACOSX und .DS_Store prüfen
make check-version   # Zeigt aktuelle Version aus composer.json
```

### 🧹 Cleanup

```bash
make clean              # Build-Artefakte löschen
make clean-releases     # Alle Release-ZIPs löschen
```

### 📊 Version Management

```bash
make version           # Aktuelle Version anzeigen
make bump-patch        # 1.0.0 → 1.0.1
make bump-minor        # 1.0.0 → 1.1.0
make bump-major        # 1.0.0 → 2.0.0
```

Version wird automatisch in diesen Dateien aktualisiert:
- `composer.json`
- `src/Resources/config/plugin.xml`
- `src/Coding9KIDataSelector.php`

### 🐳 Docker Commands

```bash
make docker-build      # ZIP im Docker-Container erstellen
make docker-install    # Plugin im Container installieren
```

### 🔧 Utilities

```bash
make list-releases     # Alle Release-ZIPs auflisten
make show-excludes     # Zeigt, was vom ZIP ausgeschlossen wird
```

---

## Workflow Examples

### Standard Release

```bash
# 1. Version bumpen (optional)
make bump-patch

# 2. Release erstellen
make release

# 3. ZIP wird erstellt unter:
# releases/Coding9KIDataSelector-1.0.1.zip
```

### Mit Docker

```bash
# Im Docker-Container bauen
make docker-build

# Im Docker-Container installieren
make docker-install
```

### Lokale Installation (für Testing)

```bash
# Plugin in lokale Shopware-Installation kopieren
make install SHOPWARE_ROOT=/path/to/shopware
```

---

## Was wird vom ZIP ausgeschlossen?

Das Makefile schließt automatisch folgendes aus:

- ❌ `.DS_Store` (macOS)
- ❌ `__MACOSX` (macOS)
- ❌ `.git*` (Git-Dateien)
- ❌ `node_modules/` (NPM)
- ❌ `vendor/` (Composer)
- ❌ `.idea/`, `.vscode/` (IDEs)
- ❌ `*.log` (Logs)
- ❌ `test*/`, `Test*/` (Tests)
- ❌ `Makefile`, `BUILD.md` (Build-Dateien)
- ❌ `releases/`, `build/` (Build-Artefakte)

---

## Verzeichnisstruktur nach Build

```
Coding9KIDataSelector/
├── releases/
│   ├── Coding9KIDataSelector-1.0.0.zip
│   ├── Coding9KIDataSelector-1.0.1.zip
│   └── ...
├── src/
├── composer.json
├── Makefile
└── ...
```

---

## Fehlerbehandlung

### "Could not detect version"

→ Prüfe, ob `composer.json` eine gültige Version hat:
```json
{
  "version": "1.0.0"
}
```

### "__MACOSX found in ZIP"

→ Das Makefile sollte das automatisch verhindern. Falls nicht:
```bash
make clean
make release
```

### "SHOPWARE_ROOT not set"

→ Setze den Pfad zu deiner Shopware-Installation:
```bash
make install SHOPWARE_ROOT=/Users/you/shopware
```

---

## CI/CD Integration

Das Makefile kann in CI/CD Pipelines verwendet werden:

```yaml
# GitHub Actions Example
- name: Build Release
  run: make release

- name: Upload Release
  uses: actions/upload-artifact@v2
  with:
    name: plugin-release
    path: releases/*.zip
```

---

## Shopware Store Upload

Nach dem Build:

```bash
# 1. Release erstellen
make release

# 2. ZIP hochladen
# releases/Coding9KIDataSelector-1.0.0.zip → Shopware Store

# 3. Store-Kompatibilität prüfen
# - Shopware 6.4.5 - 6.7.2
# - PHP >= 8.0
```

---

## Tipps & Tricks

### Schneller Workflow

```bash
# Alias in ~/.bashrc oder ~/.zshrc:
alias plugin-release='make release && ls -lh releases/'

# Dann einfach:
plugin-release
```

### Version vor Release prüfen

```bash
# Zeigt aktuelle Version
make version

# Wenn nicht korrekt, bumpen:
make bump-patch

# Dann Release
make release
```

### Backup alter Releases

Das Makefile erstellt automatisch Backups:
- Wenn `Coding9KIDataSelector-1.0.0.zip` existiert
- Wird umbenannt zu `Coding9KIDataSelector-1.0.0.zip.backup`

---

## Support

Bei Problemen:
1. `make help` für alle Commands
2. `make show-excludes` um zu sehen, was ausgeschlossen wird
3. `make verify-zip` um ZIP-Inhalt zu prüfen

**E-Mail:** support@coding9.de
