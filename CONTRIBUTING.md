# Contributing

Thanks for helping. This file covers how to work on the package and what a change
must do to be merged. Everyone taking part agrees to the
[Code of Conduct](CODE_OF_CONDUCT.md).

## Setup

You need PHP 8.3+ with the `sqlite`/`pdo_sqlite`, `mbstring`, `intl`, `gd`, `zip` and
`bcmath` extensions, and Composer.

```bash
git clone https://github.com/mrjthedifferent/laravel-foundation
cd laravel-foundation
composer install
```

The package is tested with [Orchestra Testbench](https://packages.tools/testbench); the
`workbench/` directory is the Laravel app the tests boot. No database server is needed:
`phpunit.xml.dist` runs everything on in-memory SQLite.

## Tests

```bash
vendor/bin/phpunit
vendor/bin/phpunit --testsuite=Modules          # Unit, Feature or Modules
vendor/bin/phpunit --filter=RolesTest
```

Package tests live in `tests/`; each module's own tests live in `modules/{Name}/tests/`.
A bug fix comes with a test that fails without it; a new feature comes with tests for it.

## Quality gate

CI (`.github/workflows/tests.yml`) runs the tests on PHP 8.3, 8.4 and 8.5, plus once
against the lowest allowed dependency versions. On PHP 8.4 it also runs these three
checks, and a pull request must pass all of them. Run them before pushing:

```bash
vendor/bin/pint --test
vendor/bin/phpstan analyse --memory-limit=1G --no-progress
vendor/bin/rector process --dry-run --no-progress-bar
```

- **Pint** (`pint.json`, Laravel preset). Run `vendor/bin/pint` to fix style rather
  than fixing it by hand.
- **PHPStan** (Larastan, level 5, `phpstan.neon`). Known existing errors are recorded
  in `phpstan-baseline.neon`. The baseline may only shrink: fix new errors, don't add
  them to it. If your change fixes a baselined error, regenerate the baseline with
  `vendor/bin/phpstan analyse --memory-limit=1G --generate-baseline` and commit the
  smaller file.
- **Rector** (`rector.php`) is a narrow set of type and strict-types rules. If the
  dry-run reports a diff, apply it with `vendor/bin/rector process` and review it.

## Working against a real project

To try a change in an application, point the application at your checkout with a
Composer `path` repository. In the application's `composer.json`:

```json
"repositories": [
    { "type": "path", "url": "../laravel-foundation", "options": { "symlink": true } }
]
```

```bash
composer require mrjthedifferent/laravel-foundation:@dev
php artisan foundation:publish --link
```

`--link` symlinks the theme into `public/assets` instead of copying it, so edits under
`ui/` show up without re-publishing. The skeleton at
[laravel-foundation-skeleton](https://github.com/mrjthedifferent/laravel-foundation-skeleton)
is a convenient application to use.

## Rules for a change

Projects update this package with `composer update`, so it must never break them
silently.

- **Released migrations are never edited, only added.** A migration that has shipped
  in a tag has already run in projects; changing it does nothing there and makes fresh
  installs differ from upgraded ones. Change the schema with a new migration.
- **Every user-facing string goes in a lang file**, read with `__()`: shared strings in
  `lang/en/foundation.php`, a module's in `modules/{Name}/lang/en/{alias}.php`.
  Identifiers (permission names, setting keys, route names, stored values) stay
  English, and config or database labels are shown with `display_label()`. The full
  rules are in the "Translations" section of
  [`sync/guidelines/views.md`](sync/guidelines/views.md).
- **A behavior change gets a [CHANGELOG.md](CHANGELOG.md) entry** under the next
  version, describing what changed for a project.
- **If a project must act** (edit its code, publish a file, run a command, change
  config), also add the steps to [UPGRADE.md](UPGRADE.md).
- Keep the package overridable: a project should be able to change it without
  forking (see "Customising without forking" in the [README](README.md)).
- Keep pull requests to one concern. Discuss large or breaking changes in an issue
  first.

## Security issues

Do not open a public issue or pull request for a vulnerability. Follow
[SECURITY.md](SECURITY.md).
