# Multi-Version Support - Quick Start

Your Coding9 KI Data Selector Plugin now supports **Shopware 6.4, 6.5, 6.6, and 6.7**!

## What Changed?

The plugin now includes a compatibility layer inspired by the Mollie Payments plugin that automatically:

1. Detects the running Shopware version
2. Loads version-specific services and configurations
3. Provides backward compatibility through polyfills
4. Handles version-specific assets

## Key Features

### Automatic Version Detection

The plugin automatically detects the Shopware version at runtime and loads the appropriate configurations:

```
6.4.x → Loads: all_versions.xml
6.5.x → Loads: all_versions.xml + 6.5.0.0.xml
6.6.x → Loads: all_versions.xml + 6.5.0.0.xml + 6.6.0.0.xml
6.7.x → Loads: all_versions.xml + 6.5.0.0.xml + 6.6.0.0.xml + 6.7.0.0.xml
```

### New Files Added

```
src/
├── Compatibility/
│   ├── DependencyLoader.php       # Loads version-specific dependencies
│   └── VersionCompare.php         # Version comparison utilities
└── Resources/
    └── config/
        └── compatibility/
            ├── all_versions.xml   # Base services (all versions)
            ├── 6.5.0.0.xml        # 6.5+ specific services
            ├── 6.6.0.0.xml        # 6.6+ specific services
            └── 6.7.0.0.xml        # 6.7+ specific services

polyfill/                          # Backward compatibility classes
├── README.md
└── Shopware/
    └── Core/
        └── [Future polyfills]

switch-composer.php                # Version switcher utility
COMPATIBILITY.md                   # Detailed documentation
```

## How to Use

### For End Users

Nothing changes! The plugin works the same way across all supported Shopware versions.

### For Developers

#### Development Mode

Switch to development mode to allow any Shopware version:

```bash
cd src/custom/plugins/Coding9KIDataSelector
php switch-composer.php dev
composer update
```

#### Production Mode

Switch to production mode before release:

```bash
php switch-composer.php prod
composer update
```

#### Adding Version-Specific Code

**Example 1: Load a service only in Shopware 6.6+**

Edit `src/Resources/config/compatibility/6.6.0.0.xml`:

```xml
<services>
    <service id="Coding9\KIDataSelector\Service\NewFeature">
        <!-- Your service definition -->
    </service>
</services>
```

**Example 2: Use version detection in PHP**

```php
use Coding9\KIDataSelector\Compatibility\VersionCompare;

$versionCompare = new VersionCompare('6.5.0.0');

if ($versionCompare->gte('6.6.0.0')) {
    // Use 6.6+ features
} else {
    // Use fallback for older versions
}
```

**Example 3: Add a polyfill for backward compatibility**

If you need a class from Shopware 6.7 to work in 6.4:

```php
// polyfill/Shopware/Core/Framework/NewClass.php
<?php
namespace Shopware\Core\Framework;

if (!class_exists(NewClass::class)) {
    class NewClass {
        // Backward-compatible implementation
    }
}
```

## Testing

Test your plugin in all supported versions:

1. Set up Shopware 6.4, 6.5, 6.6, and 6.7 environments
2. Install the plugin in each
3. Verify all features work correctly

## Further Reading

See [COMPATIBILITY.md](./COMPATIBILITY.md) for detailed documentation including:

- Architecture deep-dive
- Advanced usage patterns
- Troubleshooting guide
- Best practices

## Credits

This multi-version compatibility system is inspired by the excellent [Mollie Payments Plugin](https://github.com/mollie/Shopware6).

## Support

For questions or issues, contact:
- Email: support@coding9.de
- Documentation: See COMPATIBILITY.md
