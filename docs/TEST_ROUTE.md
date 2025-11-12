# Test the Routes

After clearing cache, test with:

```bash
# Test if route is registered
bin/console router:match /api/_action/kidata/query

# Or check all routes
bin/console debug:router | grep kidata
```

If routes show up, test with curl:

```bash
curl -X POST http://localhost/api/_action/kidata/query \
  -H "Content-Type: application/json" \
  -d '{"prompt":"SELECT 1 as test","execute":false}'
```
