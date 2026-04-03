## Testing

- Never pass attributes directly to `create()` or `make()` in tests. Instead, define named state methods on the factory and chain them in the test (e.g. `->asCaptain()`, `->withRole(...)`).

## Rector

- After modifying PHP files, run `vendor/bin/rector process --clear-cache --memory-limit=2G` and fix any issues before finalizing changes.
