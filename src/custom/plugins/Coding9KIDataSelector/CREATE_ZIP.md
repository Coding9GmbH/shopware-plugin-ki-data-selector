# ZIP-Erstellung für Shopware Store Upload

## Problem mit __MACOSX Ordner

macOS erstellt beim Komprimieren automatisch einen `__MACOSX` Ordner, der vom Shopware Store nicht akzeptiert wird.

## Lösung 1: Terminal (Empfohlen)

Verwende das Terminal, um ein ZIP ohne macOS-spezifische Dateien zu erstellen:

```bash
# Wechsle in das Verzeichnis, das die Plugin-Ordner enthält
cd /Users/alexanderschikowsky/Work/hgs/hgs-shop-sw5/custom/plugins/

# Erstelle ZIP ohne __MACOSX und .DS_Store Dateien
zip -r Coding9KIDataSelector-1.0.0.zip Coding9KIDataSelector \
  -x "*.DS_Store" \
  -x "*__MACOSX*" \
  -x "*.git*" \
  -x "*node_modules*" \
  -x "*.idea*"
```

Das ZIP liegt dann unter: `/Users/alexanderschikowsky/Work/hgs/hgs-shop-sw5/custom/plugins/Coding9KIDataSelector-1.0.0.zip`

## Lösung 2: Vorhandenes ZIP bereinigen

Falls du bereits ein ZIP hast:

```bash
# Entferne __MACOSX aus bestehendem ZIP
zip -d Coding9KIDataSelector-1.0.0.zip "__MACOSX/*"
```

## Lösung 3: ditto Command (Alternative)

```bash
cd /Users/alexanderschikowsky/Work/hgs/hgs-shop-sw5/custom/plugins/

# Temporären Ordner erstellen und Dateien kopieren (ohne macOS Metadaten)
ditto -c -k --sequesterRsrc --keepParent Coding9KIDataSelector Coding9KIDataSelector-1.0.0.zip
```

## Verifikation

Prüfe den Inhalt des ZIP:

```bash
unzip -l Coding9KIDataSelector-1.0.0.zip | grep -i macosx
```

Wenn keine Ausgabe erscheint, ist das ZIP sauber!

## Wichtig vor dem Upload

1. ✅ Constructor Property Promotion wurde zu traditioneller Syntax konvertiert
2. ✅ License auf "proprietary" geändert
3. ✅ Shopware Version-Range angepasst: `~6.4.0|~6.5.0|~6.6.0|~6.7.0`
4. ✅ Plugin-Icon ist vorhanden unter `src/Resources/config/plugin.png`
5. ✅ Alle PHP Parse-Errors behoben

## Store-Kompatibilität in Account einstellen

Stelle sicher, dass im Shopware Store Account die Kompatibilität auf **6.7.0.1** oder niedriger eingestellt ist, um die Versionsangaben mit composer.json kompatibel zu machen.
