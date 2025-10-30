# Polyfills Directory

This directory contains polyfill classes and interfaces that provide backward compatibility
for different Shopware versions (6.4, 6.5, 6.6, 6.7).

## Purpose

Polyfills are used to provide classes/interfaces that exist in newer Shopware versions
but are missing in older versions. This allows the plugin to use modern APIs while
still maintaining compatibility with older Shopware installations.

## How it works

1. The polyfills are registered in the `DependencyLoader` class via Composer's ClassLoader
2. If a class already exists in the Shopware core (newer versions), the core class is used
3. If a class doesn't exist (older versions), the polyfill is loaded

## Structure

```
polyfill/
└── Shopware/
    └── Core/
        └── [Polyfill Classes]
```

The directory structure mirrors Shopware's namespace structure.

## Example

If Shopware 6.7 introduces a new `PaymentException` class that doesn't exist in 6.4-6.6,
we create a polyfill here:

```
polyfill/Shopware/Core/Checkout/Payment/PaymentException.php
```

This way, our plugin can use `PaymentException` in all versions.
