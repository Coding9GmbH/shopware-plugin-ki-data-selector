# Administration Multi-Version Compatibility

This document explains how the administration components handle multi-version compatibility.

## Overview

The administration components now use a `VersionHelper` utility to ensure compatibility across Shopware 6.4 - 6.7.

## Key Changes

### 1. VersionHelper Utility (`src/core/version-helper.js`)

A central utility class that provides version-safe methods for:

- **API Authentication**: `getAuthToken()`, `getApiHeaders()`
- **API Requests**: `apiFetch()`, `apiPost()`
- **Clipboard Operations**: `copyToClipboard()`
- **File Downloads**: `downloadBlob()`
- **Version Detection**: `getShopwareVersion()`, `isVersionGte()`

### 2. Updated Components

All three main components have been updated:

#### kidata-selector-index
- Uses `VersionHelper.apiPost()` for API calls
- Uses `VersionHelper.copyToClipboard()` for clipboard operations
- Uses `VersionHelper.downloadBlob()` for CSV exports

#### kidata-selector-list
- Uses `VersionHelper.apiFetch()` for GET/DELETE requests
- Properly handles authentication across versions

#### kidata-selector-detail
- Uses `VersionHelper.apiPost()` for query execution
- Uses `VersionHelper.copyToClipboard()` for SQL copying
- Uses `VersionHelper.downloadBlob()` for exports

## Why This Is Needed

### Authentication Token Changes

Different Shopware versions expose the auth token differently:

**6.4.x:**
```javascript
Shopware.Context.api.authToken // Direct token
```

**6.5+:**
```javascript
Shopware.Context.api.authToken.access // Token object
```

The `VersionHelper` handles both cases automatically.

### API Service Access

Different versions have different service container APIs:

**6.4:**
```javascript
Shopware.Application.getContainer('service').get(name)
```

**6.5+:**
```javascript
Shopware.Service(name)
```

### Clipboard API

Older browsers/versions may not support the modern Clipboard API, so we provide a fallback.

## Usage Examples

### Making an API Call

```javascript
// Old way (version-specific)
const response = await fetch('/api/_action/kidata/query', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${Shopware.Context.api.authToken.access}`
    },
    body: JSON.stringify(data)
});

// New way (version-safe)
const result = await VersionHelper.apiPost('/api/_action/kidata/query', data);
```

### Copying to Clipboard

```javascript
// Old way (might fail in some browsers)
navigator.clipboard.writeText(text);

// New way (with fallback)
await VersionHelper.copyToClipboard(text);
```

### Downloading Files

```javascript
// Old way (manual DOM manipulation)
const url = window.URL.createObjectURL(blob);
const a = document.createElement('a');
a.href = url;
a.download = filename;
document.body.appendChild(a);
a.click();
document.body.removeChild(a);
window.URL.revokeObjectURL(url);

// New way (cleaner, with proper cleanup)
VersionHelper.downloadBlob(blob, filename);
```

## Version Detection

You can check the Shopware version in your components:

```javascript
import VersionHelper from '../../../../core/version-helper';

// Check if version is >= 6.5
if (VersionHelper.isVersionGte('6.5.0.0')) {
    // Use 6.5+ features
} else {
    // Fallback for 6.4
}
```

## API Stability

### Stable APIs (6.4 - 6.7)
These APIs work across all versions without changes:

- ✅ `Module.register()`
- ✅ `Component.register()`
- ✅ `Mixin.getByName()`
- ✅ `this.$router`
- ✅ `this.$tc()` (i18n)
- ✅ `Shopware.Data.Criteria`

### Potentially Unstable APIs
These may vary between versions (now handled by VersionHelper):

- ⚠️ `Shopware.Context.api.authToken`
- ⚠️ `Shopware.Service()`
- ⚠️ `navigator.clipboard`

## Testing

When testing across versions, verify:

1. **Authentication works** - Login and API calls succeed
2. **Clipboard operations work** - Copy SQL to clipboard
3. **File downloads work** - Export CSV files
4. **API calls succeed** - Query generation, execution, saving

## Future-Proofing

If Shopware introduces breaking changes in future versions:

1. Update `VersionHelper` with new detection logic
2. Add version-specific branches in the helper methods
3. No need to change individual components

## Best Practices

1. **Always use VersionHelper** for API calls, auth, clipboard, and downloads
2. **Don't access `Shopware.Context.api.authToken` directly** - Use `VersionHelper.getAuthToken()`
3. **Test in multiple versions** - Especially 6.4 (oldest) and latest
4. **Log errors** - VersionHelper logs warnings when it can't detect version

## Troubleshooting

### "Could not retrieve auth token"

- Check that user is logged in
- Verify Shopware version is supported (6.4.5.0+)
- Check browser console for detailed error

### API calls fail with 401

- Auth token may be expired - refresh the page
- Check that VersionHelper is correctly getting the token

### Clipboard doesn't work

- Some browsers require HTTPS for clipboard API
- VersionHelper should fall back to manual selection method

### File downloads don't start

- Check browser's download settings
- Verify the blob is correctly created
- Check for popup blockers

## Migration Guide

If you're adding a new component, follow this pattern:

```javascript
import VersionHelper from '../../../../core/version-helper';

Component.register('my-component', {
    methods: {
        async myApiCall() {
            // Use VersionHelper instead of direct fetch
            const data = await VersionHelper.apiPost('/api/endpoint', {
                param: 'value'
            });
        },

        async copyText() {
            // Use VersionHelper for clipboard
            await VersionHelper.copyToClipboard(this.text);
        }
    }
});
```

## Related Documentation

- [Main Compatibility Guide](../../../../../../COMPATIBILITY.md)
- [Multi-Version Support Overview](../../../../../../MULTI_VERSION_SUPPORT.md)
- [Shopware Documentation](https://developer.shopware.com/)
