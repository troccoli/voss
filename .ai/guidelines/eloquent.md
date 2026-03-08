=== laravel/core rules ===

# Do Things the Laravel Way

## Database

## Pivot Tables

- Pivot table names must use the **singular** names of both related models in **alphabetical order**, joined with `_`. For example, `Game` + `Player` → `game_player`, not `roster_players` or `game_players`. Following this convention means the table name does not need to be specified in `belongsToMany()`.
- Pivot model classes extend `Illuminate\Database\Eloquent\Relations\Pivot`, not `Model`. They do not use `HasFactory`.
- Add `public $incrementing = true` on any Pivot model whose table has an auto-increment primary key.

## Eloquent

- Always use `getKey()` to retrieve a model's primary key value, never `->id`. This works correctly regardless of the primary key column name.
- Always import `Illuminate\Database\Eloquent\Collection` with the `EloquentCollection` alias: `use Illuminate\Database\Eloquent\Collection as EloquentCollection;`. This prevents naming collisions with `Illuminate\Support\Collection` when both are needed in the same file.
