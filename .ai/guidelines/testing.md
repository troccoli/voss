## Testing

- Never pass attributes directly to `create()` or `make()` in tests. Instead, define named state methods on the factory and chain them in the test (e.g. `->asCaptain()`, `->withRole(...)`).
