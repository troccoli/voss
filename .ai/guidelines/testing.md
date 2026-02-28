## Testing

- Never pass attributes directly to `create()` or `make()` in tests. Instead, define named state methods on the factory and chain them in the test (e.g. `->asCaptain()`, `->withRole(...)`).

## Static Analysis

- After modifying PHP files, run `vendor/bin/phpstan analyse --memory-limit=256M` and fix any errors before finalizing changes.
