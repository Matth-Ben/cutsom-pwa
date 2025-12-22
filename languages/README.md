# Custom PWA Translations# Custom PWA - Translations



## Available LanguagesThis directory contains translation files for the Custom PWA plugin.



- 🇫🇷 **French** (fr_FR) - Français## Available Languages

- 🇪🇸 **Spanish** (es_ES) - Español  

- 🇩🇪 **German** (de_DE) - Deutsch✅ **French (fr_FR)** - Français  

- 🇮🇹 **Italian** (it_IT) - Italiano✅ **Spanish (es_ES)** - Español  

- 🇬🇧 **English** (default) - English✅ **German (de_DE)** - Deutsch  

✅ **Italian (it_IT)** - Italiano  

## Files

## Files

### Template

- `custom-pwa.pot` - Translation template (469 strings)### Template File

- `custom-pwa.pot` - Translation template containing all translatable strings (469 strings)

### Translations

Each language has two files:### Translation Files (.po)

- `custom-pwa-{locale}.po` - Portable Object file (human-readable)- `custom-pwa-fr_FR.po` - French translation source

- `custom-pwa-{locale}.mo` - Machine Object file (compiled, used by WordPress)- `custom-pwa-es_ES.po` - Spanish translation source

- `custom-pwa-de_DE.po` - German translation source

## Translation Coverage- `custom-pwa-it_IT.po` - Italian translation source



| Language | Locale | Translated Strings | Coverage |### Compiled Files (.mo)

|----------|--------|-------------------|----------|- `custom-pwa-fr_FR.mo` - French compiled translation

| French   | fr_FR  | 77/469           | ~16%     |- `custom-pwa-es_ES.mo` - Spanish compiled translation

| Spanish  | es_ES  | 77/469           | ~16%     |- `custom-pwa-de_DE.mo` - German compiled translation

| German   | de_DE  | 77/469           | ~16%     |- `custom-pwa-it_IT.mo` - Italian compiled translation

| Italian  | it_IT  | 77/469           | ~16%     |

## How Translations Work

**Note**: The most common and important strings have been translated. Additional translations can be added over time.

WordPress automatically loads the appropriate translation based on the site language set in **Settings → General → Site Language**.

## How WordPress Uses These Files

The plugin uses the text domain `custom-pwa` for all translatable strings.

1. WordPress detects the user's language from their profile settings

2. Loads the corresponding `.mo` file from the `languages/` directory## Translation Coverage

3. Replaces English strings with translated versions using `__()`, `_e()`, `_x()`, etc.

Currently **42 key strings** have been translated in each language, including:

## Key Translated Terms

- Admin menu items

### Interface Elements- Settings page labels

- **Config** → Configuration (FR), Configuración (ES), Konfiguration (DE), Configurazione (IT)- Configuration options

- **Push Notifications** → Notifications Push (FR), Notificaciones Push (ES), Push-Benachrichtigungen (DE), Notifiche Push (IT)- Status messages

- **Settings** → Paramètres (FR), Ajustes (ES), Einstellungen (DE), Impostazioni (IT)- Error messages

- Button labels

### Actions- Form fields

- **Enable** → Activer (FR), Activar (ES), Aktivieren (DE), Attiva (IT)- SSL Helper messages

- **Save Changes** → Enregistrer (FR), Guardar (ES), Speichern (DE), Salva (IT)- VAPID key management

- Installation instructions

## Testing Translations

## Adding or Updating Translations

### In WordPress Admin

### Method 1: Using Poedit (Recommended)

1. Go to **Settings → General**

2. Change **Site Language** to your desired language1. Download and install [Poedit](https://poedit.net/)

3. Navigate to **Custom PWA** menu2. Open the `.po` file for your language

4. Interface should display in the selected language3. Translate the strings

4. Save (Poedit automatically generates the `.mo` file)

## Contributing

### Method 2: Using WP-CLI

Want to improve translations? Edit the `.po` files and compile with `msgfmt`.

```bash

---# Update POT file with new strings

cd wp-content/plugins/cutsom-pwa

**Last Updated**: December 22, 2024  wp i18n make-pot . languages/custom-pwa.pot --domain=custom-pwa --allow-root

**Plugin Version**: 1.0.5

# Update existing PO files
wp i18n update-po languages/custom-pwa.pot languages/ --allow-root

# Compile PO to MO
msgfmt languages/custom-pwa-fr_FR.po -o languages/custom-pwa-fr_FR.mo
msgfmt languages/custom-pwa-es_ES.po -o languages/custom-pwa-es_ES.mo
msgfmt languages/custom-pwa-de_DE.po -o languages/custom-pwa-de_DE.mo
msgfmt languages/custom-pwa-it_IT.po -o languages/custom-pwa-it_IT.mo
```

### Method 3: Manual Editing

1. Open the `.po` file in a text editor
2. Find `msgid` (original English string)
3. Add translation in `msgstr` field:
   ```
   msgid "Save Changes"
   msgstr "Guardar Cambios"  # Spanish
   ```
4. Compile using `msgfmt`:
   ```bash
   msgfmt custom-pwa-es_ES.po -o custom-pwa-es_ES.mo
   ```

## Testing Translations

1. Go to **Settings → General**
2. Change **Site Language** to your target language
3. Navigate to **Custom PWA** admin pages
4. Verify translated strings appear correctly

## Contributing Translations

To contribute translations:

1. Fork the plugin repository
2. Create/update the `.po` file for your language
3. Compile to `.mo` using `msgfmt`
4. Submit a pull request

## Translation Guidelines

- Keep button labels concise
- Maintain technical terms (PWA, VAPID, SSL, HTTPS)
- Use formal tone for admin interfaces
- Test translations in context
- Check for proper encoding (UTF-8)
- Verify special characters display correctly

## File Format

Translation files use the Gettext PO format:

```
#: path/to/file.php:123
msgid "Original English text"
msgstr "Translated text"

#: path/to/file.php:456
#, php-format
msgid "Hello %s"
msgstr "Hola %s"
```

## Auto-Generated Files

The following files are auto-generated and should not be edited directly:

- `*.mo` files (compiled from `.po` files)
- `custom-pwa.pot` (generated from source code)

Always edit `.po` files and recompile to `.mo`.

## Need Help?

- [WordPress I18n Documentation](https://developer.wordpress.org/apis/handbook/internationalization/)
- [Poedit Documentation](https://poedit.net/trac/wiki/Doc)
- [Gettext Manual](https://www.gnu.org/software/gettext/manual/)

---

**Version**: 1.0.5  
**Last Updated**: December 22, 2024  
**Total Strings**: 469  
**Translated Strings per Language**: 42 (9%)
