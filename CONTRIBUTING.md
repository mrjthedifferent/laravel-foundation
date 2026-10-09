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

## The UI: stylesheet and JavaScript

The admin UI's stylesheet (Tailwind) and script (`foundation.src.js`, minified with esbuild) are built **inside this repository** and the results committed, so projects need no build step.
Edit `ui/resources/js/foundation.src.js`, never the minified `ui/public/assets/js/foundation.js`.
Change `ui/resources/css/**` or any Blade view, JavaScript string or PHP class that carries class names, then rebuild:

```bash
bash bin/build-css.sh           # writes ui/public/assets/css/foundation.css
bash bin/build-css.sh --check   # what CI runs: fails if the committed file is stale
bash bin/build-js.sh            # minifies ui/resources/js/foundation.src.js to ui/public/assets/js/foundation.js
bash bin/build-js.sh --check    # what CI runs: fails if the committed file is stale
bash bin/test-js.sh             # behaviour tests, run against the minified file that ships (Node + jsdom)
```

A utility class a project may use without building Tailwind itself belongs in `ui/resources/css/safelist.css`. The guidelines in
`sync/guidelines/` are what AI assistants read in every project; a test fails if a Blade component or dashboard extension point
is added without being documented in them.

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

- **A released migration is never edited.** Once a migration has shipped in a tag it
  has run in projects, so editing it changes nothing there. Change the schema with a
  new migration.
- **Every user-facing string goes in a lang file**, read with `__()`: shared strings in
  `lang/en/foundation.php`, a module's in `modules/{Name}/lang/en/{alias}.php`.
  Identifiers (permission names, setting keys, route names, stored values) stay
  English, and config or database labels are shown with `display_label()`. The full
  rules are in the "Translations" section of
  [`sync/guidelines/views.md`](sync/guidelines/views.md).
- **A behavior change gets a [CHANGELOG.md](CHANGELOG.md) entry** under the next
  version, describing what changed for a project and, if a project must act (edit its
  code, publish a file, run a command, change config), exactly what to do.
- Keep the package overridable: a project should be able to change it without
  forking (see "Customising without forking" in the [README](README.md)).
- Keep pull requests to one concern. Discuss large or breaking changes in an issue
  first.

## Releasing

For maintainers. Releases are cut from `main`; there are no release branches.

1. Run the tests and the three quality checks above.
2. Rename the changelog's pending section to the new version (a fix bumps the patch
   number, a feature the minor), commit it as `Release x.y.z` and push.
3. Wait for CI on that commit to pass, then tag and publish:

   ```bash
   git tag -a vx.y.z -m "vx.y.z — one-line summary"
   git push origin vx.y.z
   gh release create vx.y.z --title vx.y.z --notes "..."
   ```

   Packagist picks up the tag within a minute. Never move or delete a tag once it is
   pushed: projects may already have installed it.
4. Update the [skeleton](https://github.com/mrjthedifferent/laravel-foundation-skeleton):
   `composer update mrjthedifferent/laravel-foundation --with-dependencies`, then
   `php artisan migrate:fresh --seed`, `php artisan test` and `php artisan optimize`.
   Commit `composer.lock` together with any file `foundation:sync` added under `.ai/`, and
   push; the skeleton's CI must pass too.

## Security issues

Do not open a public issue or pull request for a vulnerability. Follow
[SECURITY.md](SECURITY.md).
