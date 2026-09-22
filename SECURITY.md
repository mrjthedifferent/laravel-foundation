# Security Policy

## Supported Versions

| Version | Supported |
|---------|-----------|
| 1.x     | Yes       |

Security fixes are released for the latest 1.x version.

## Reporting a Vulnerability

Please **do not** open a public GitHub issue for a security vulnerability.

Report it privately through
[GitHub Security Advisories](https://github.com/mrjthedifferent/laravel-foundation/security/advisories/new)
for this repository. This reaches the maintainer directly and keeps the report
confidential while a fix is prepared.

Include, as far as you can:

- A description of the vulnerability and its impact.
- Steps to reproduce it (a minimal repro against a fresh `foundation:install` is ideal).
- The package version (or commit) affected.

You should receive an initial response within a few days. Once a fix is
confirmed, a patch release will be published and the advisory credited to you
(unless you prefer to stay anonymous), coordinated with you on timing where
practical.

## Scope

This package ships an admin panel foundation (authentication, roles and
permissions, settings, notifications, activity logs, file uploads) intended to
be installed into a Laravel application. In scope: vulnerabilities in the
package's own code (`src/`, `modules/`, `ui/`) reachable through its documented
routes, commands, or public API. Out of scope: vulnerabilities in Laravel
itself or in third-party dependencies (report those upstream), and
misconfiguration of a consuming application (e.g. a weak `APP_KEY`, a Super
Admin granted to the wrong account, or disabled TLS verification at the
infrastructure level).
