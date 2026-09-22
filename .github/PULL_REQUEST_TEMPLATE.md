## What and why

<!-- What this changes, and the problem it solves. Link the issue if there is one. -->

## Checklist

- [ ] Tests added or updated, and `vendor/bin/phpunit` passes
- [ ] `vendor/bin/pint --test`, `vendor/bin/phpstan analyse --memory-limit=1G --no-progress` and `vendor/bin/rector process --dry-run --no-progress-bar` pass (no new PHPStan baseline entries)
- [ ] Behavior change: CHANGELOG.md entry added
- [ ] A breaking change says in CHANGELOG.md what a project must do
- [ ] New user-facing strings are in a lang file, read with `__()`
- [ ] No released migration edited (schema changes are a new migration)

See [CONTRIBUTING.md](../CONTRIBUTING.md) for the details behind each item.
