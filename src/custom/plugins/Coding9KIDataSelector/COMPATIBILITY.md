# Multi-Version Compatibility Guide

This plugin supports Shopware versions **6.4.5.0 through 6.7.0.0** using a sophisticated compatibility layer inspired by the Mollie Payments plugin.

## Table of Contents

- [Overview](#overview)
- [Architecture](#architecture)
- [Directory Structure](#directory-structure)
- [How It Works](#how-it-works)
- [Adding Version-Specific Features](#adding-version-specific-features)
- [Development Workflow](#development-workflow)
- [Testing Across Versions](#testing-across-versions)

## Overview

The plugin uses a **runtime version detection** system that:

1. Detects the current Shopware version at boot time
2. Loads version-specific services and configurations
3. Provides polyfills for backward compatibility
4. Handles version-specific asset compilation

## Architecture

### Core Components

#### 1. VersionCompare (`src/Compatibility/VersionCompare.php`)

Provides version comparison utilities:

```php
$versionCompare = new VersionCompare('6.5.0.0');

if ($versionCompare->gte('6.5.0.0')) {
    // Load 6.5+ specific code
}

if ($versionCompare->lt('6.6.0.0')) {
    // Load code for versions < 6.6
}
```

Available methods:
- `gte($version)` - Greater than or equal
- `gt($version)` - Greater than
- `lt($version)` - Less than
- `lte($version)` - Less than or equal
- `getVersion()` - Get current version

#### 2. DependencyLoader (`src/Compatibility/DependencyLoader.php`)

Handles version-specific dependency loading:

- **loadServices()** - Loads version-specific XML service configurations
- **registerDependencies()** - Registers polyfills and other dependencies
- **prepareAdministrationBuild()** - Prepares version-specific frontend assets

#### 3. Main Plugin Class (`src/Coding9KIDataSelector.php`)

Integrates the compatibility system:

```php
public function build(ContainerBuilder $container): void
{
    parent::build($container);

    $shopwareVersion = $container->getParameter('kernel.shopware_version');
    $loader = new DependencyLoader($container, new VersionCompare($shopwareVersion));

    $loader->loadServices();
    $loader->prepareAdministrationBuild();
}

public function boot(): void
{
    parent::boot();

    $loader = new DependencyLoader($this->container, new VersionCompare($shopwareVersion));
    $loader->registerDependencies();
}
```

## Directory Structure

```
Coding9KIDataSelector/
├── src/
│   ├── Compatibility/
│   │   ├── DependencyLoader.php      # Version-specific loader
│   │   └── VersionCompare.php        # Version comparison utility
│   ├── Resources/
│   │   └── config/
│   │       └── compatibility/
│   │           ├── all_versions.xml  # Services for all versions
│   │           ├── 6.5.0.0.xml       # Services for 6.5+
│   │           ├── 6.6.0.0.xml       # Services for 6.6+
│   │           └── 6.7.0.0.xml       # Services for 6.7+
│   └── Coding9KIDataSelector.php
├── polyfill/
│   └── Shopware/
│       └── Core/
│           └── [Polyfill Classes]    # Backward compatibility classes
├── composer.json
└── switch-composer.php               # Version switcher utility
```

## How It Works

### 1. Boot Process

When the plugin boots:

1. **Version Detection**: The plugin detects the current Shopware version from the kernel
2. **Service Loading**: Version-specific service XMLs are loaded based on the detected version
3. **Polyfill Registration**: Polyfills are registered for backward compatibility
4. **Asset Preparation**: Version-specific frontend assets are prepared

### 2. Service Loading Order

Services are loaded in this order:

1. `all_versions.xml` - Base services for all versions
2. `6.5.0.0.xml` - Additional services for 6.5+ (if applicable)
3. `6.6.0.0.xml` - Additional services for 6.6+ (if applicable)
4. `6.7.0.0.xml` - Additional services for 6.7+ (if applicable)

Later configurations can override earlier ones, allowing version-specific customizations.

### 3. Polyfills

Polyfills provide classes/interfaces that exist in newer Shopware versions but not in older ones.

**Example**: If Shopware 6.7 introduces `PaymentException`:

```php
// polyfill/Shopware/Core/Checkout/Payment/PaymentException.php
<?php
namespace Shopware\Core\Checkout\Payment;

if (!class_exists(PaymentException::class)) {
    class PaymentException extends \Exception
    {
        // Backward compatible implementation
    }
}
```

The polyfill is only used if the class doesn't exist (older Shopware versions).

## Adding Version-Specific Features

### Scenario 1: New Service for Shopware 6.6+

1. Add the service to `src/Resources/config/compatibility/6.6.0.0.xml`:

```xml
<services>
    <service id="Coding9\KIDataSelector\Service\NewFeature">
        <argument type="service" id="Shopware\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry"/>
    </service>
</services>
```

2. Use version check in your code:

```php
if ($versionCompare->gte('6.6.0.0')) {
    $newFeature = $container->get(NewFeature::class);
    $newFeature->execute();
}
```

### Scenario 2: Different Implementation for Different Versions

1. Create version-specific implementations:

```php
// src/Service/DataProcessor/DataProcessorInterface.php
interface DataProcessorInterface { }

// src/Service/DataProcessor/LegacyDataProcessor.php (for 6.4/6.5)
class LegacyDataProcessor implements DataProcessorInterface { }

// src/Service/DataProcessor/ModernDataProcessor.php (for 6.6+)
class ModernDataProcessor implements DataProcessorInterface { }
```

2. Configure in version-specific XMLs:

```xml
<!-- all_versions.xml (6.4/6.5) -->
<service id="Coding9\KIDataSelector\Service\DataProcessorInterface"
         class="Coding9\KIDataSelector\Service\DataProcessor\LegacyDataProcessor"/>

<!-- 6.6.0.0.xml -->
<service id="Coding9\KIDataSelector\Service\DataProcessorInterface"
         class="Coding9\KIDataSelector\Service\DataProcessor\ModernDataProcessor"/>
```

### Scenario 3: Adding a Polyfill

If you need a class from Shopware 6.7 in your plugin:

1. Create the polyfill:

```php
// polyfill/Shopware/Core/Framework/NewFeature/NewClass.php
<?php declare(strict_types=1);

namespace Shopware\Core\Framework\NewFeature;

if (!class_exists(NewClass::class)) {
    class NewClass
    {
        // Provide backward-compatible implementation
        public function doSomething(): void
        {
            // Implementation that works in 6.4-6.6
        }
    }
}
```

2. Use it normally in your code:

```php
use Shopware\Core\Framework\NewFeature\NewClass;

$instance = new NewClass();
$instance->doSomething();
```

## Development Workflow

### Switching Between Production and Development Mode

Use the `switch-composer.php` script to change version constraints:

```bash
# Set to production mode (6.4.5.0 - 6.7.0.0)
php switch-composer.php prod

# Set to development mode (allows any version)
php switch-composer.php dev
```

### Updating Dependencies

After switching modes, run:

```bash
composer update
```

## Testing Across Versions

### Local Testing

1. **Setup multiple Shopware installations** (6.4, 6.5, 6.6, 6.7)

2. **Install the plugin** in each environment:
```bash
bin/console plugin:install --activate Coding9KIDataSelector
bin/console cache:clear
```

3. **Test functionality** in each version:
   - Administration module loads correctly
   - API endpoints work
   - Version-specific features activate properly

### Automated Testing

Create version-specific test suites:

```php
class CompatibilityTest extends TestCase
{
    public function testServiceExistsInVersion64(): void
    {
        $versionCompare = new VersionCompare('6.4.5.0');
        // Test 6.4-specific behavior
    }

    public function testServiceExistsInVersion67(): void
    {
        $versionCompare = new VersionCompare('6.7.0.0');
        // Test 6.7-specific behavior
    }
}
```

## Best Practices

1. **Always test on the minimum supported version (6.4.5.0)** - If it works there, it likely works everywhere

2. **Use version checks sparingly** - Prefer polyfills and service overrides over inline version checks

3. **Document version-specific behavior** - Add comments explaining why certain code is version-specific

4. **Keep polyfills minimal** - Only add polyfills for classes you actually use

5. **Test upgrades** - When a new Shopware version is released, test the plugin before claiming support

## Troubleshooting

### Plugin doesn't load in version X

- Check that the service XMLs don't reference version-specific classes
- Verify polyfills are properly implemented
- Check logs for missing class errors

### Services not being overridden

- Ensure XML files are being loaded (check file paths in DependencyLoader)
- Verify service IDs match exactly
- Check XML file syntax

### Frontend assets not working

- Verify `prepareAdministrationBuild()` is running correctly
- Check that version-specific JS files exist if needed
- Clear Shopware cache: `bin/console cache:clear`

## Version-Specific Changes in Shopware

### Notable Changes Between Versions

**6.4 → 6.5**
- Entity repositories changed interface
- Flow Builder introduced
- Administration component API changes

**6.5 → 6.6**
- New exception handling pattern
- DAL improvements
- Updated Symfony components

**6.6 → 6.7**
- Further API refinements
- Performance improvements
- New core features

Consult Shopware's official upgrade guides for detailed breaking changes.

## Support

For issues related to multi-version compatibility:

1. Check this documentation first
2. Review the Mollie Payments plugin (our inspiration) for advanced patterns
3. Contact Coding9 support at support@coding9.de

## References

- Shopware Documentation: https://developer.shopware.com/
- Mollie Payments Plugin: https://github.com/mollie/Shopware6
- Plugin Development Guide: https://developer.shopware.com/docs/guides/plugins/
