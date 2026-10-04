# TestedRoutesCheckerBundle

[![Tests](https://github.com/odolbeau/TestedRoutesCheckerBundle/actions/workflows/tests.yml/badge.svg)](https://github.com/odolbeau/TestedRoutesCheckerBundle/actions/workflows/tests.yml)
[![Latest version](https://img.shields.io/packagist/v/bab/tested-routes-checker-bundle)](https://packagist.org/packages/bab/tested-routes-checker-bundle)

A bundle to ensure all routes of a Symfony application have been tested.

## How it works?

1. Launch your tests using PHPUnit or anything else. All called routes will be stored in `var/cache/bab_tested_routes_checker_bundle_route_storage`.
2. Run `php bin/console bab:tested-routes-checker:check` to have a small report of what's tested and what's not!

A route is stored each time a request matching it is handled by the kernel in the `test` environment, along with the status code of the response. This means functional tests (`WebTestCase`, `ApiTestCase`, ...) count, whereas tests which never go through the kernel don't.

A route is considered **tested** as soon as it has been called once, and **successfully tested** if at least one of the responses had a status code lower than 400.

## Installation

```console
composer require --dev bab/tested-routes-checker-bundle
```

If your application doesn't use Symfony Flex, also enable the bundle in `config/bundles.php`:

```php
// config/bundles.php

return [
    // ...
    Bab\TestedRoutesCheckerBundle\BabTestedRoutesCheckerBundle::class => ['dev' => true, 'test' => true],
];
```

## Usage

Run your tests, then:

```console
php bin/console bab:tested-routes-checker:check
```

```
Some routes have not been tested :

 * admin_dashboard
 * api_user_delete

 [ERROR] Found 2 non tested routes!
```

The command exits with a non-zero code if at least one route has not been tested, which makes it suitable for a CI. Routes which have been called but which always returned a 4xx or 5xx code are reported as well, and also make the command fail (unless you use `-S`, see below).

| Option | Description |
| --- | --- |
| `-m`, `--maximum-routes-to-display` | Maximum number of routes to display per section (default: `25`). |
| `-i`, `--routes-to-ignore` | Path to the file containing the routes to ignore (default: `.bab-trc-baseline`). |
| `-g`, `--generate-baseline` | Generate the file containing the routes to ignore (see [below](#using-baseline-to-ignore-some-routes)). |
| `-S`, `--ignore-not-successfully-tested-routes` | Don't fail if a route has been called but never returned a 1xx, 2xx or 3xx code. |

## Configuration

The bundle works without any configuration. The following options are available (default values are displayed):

```yaml
# config/packages/bab_tested_routes_checker.yaml
bab_tested_routes_checker:
    maximum_number_of_routes_to_display: 25
    routes_to_ignore_file: '%kernel.project_dir%/.bab-trc-baseline'
    route_storage_file: '%kernel.project_dir%/var/cache/bab_tested_routes_checker_bundle_route_storage'
```

## Using baseline to ignore some routes

You can ignore some routes with a `.bab-trc-baseline` file with 1 route per line. Each line is either a route name or a regular expression (`api_.*`). Empty lines and comments (a line starting with `#`, or the part of a line after ` #`) are ignored.

```
# Routes of the legacy admin, to be removed soon
admin_legacy_.*
healthcheck # called by the infrastructure only
```

To create the file from the current state of your application, run your tests and then:

```console
php bin/console bab:tested-routes-checker:check --generate-baseline
```

If the file already exists, it is updated rather than overwritten: the entries which are still useful (they match at least one route which would otherwise be reported) are kept as is, comments included, the untested routes which are not covered yet are appended, and the entries which are no longer useful (route removed or now tested) are deleted. Lines containing only a comment are always kept. With `-S`, the routes which have only returned 4xx or 5xx codes are handled like the untested ones.

The following routes are always ignored: `_profiler*`, `_wdt*`, `_webhook_controller`, `_preview_error` and `app.swagger`.

## Running tests in parallel (ParaTest)

Nothing to configure: the bundle works with [ParaTest](https://github.com/paratestphp/paratest) out of the box.

All the worker processes append to the same file (`var/cache/bab_tested_routes_checker_bundle_route_storage` by default), and each write takes an exclusive lock on it (since 1.0.2), so concurrent writes can't be interleaved or lost. Once ParaTest is done, run `bab:tested-routes-checker:check` as usual: it reads the file written by all the workers.

> [!NOTE]
> The file is never emptied by the bundle, so it keeps the routes of previous runs. Remove it before running your tests if you want a report that only reflects the current run (this is already the case on a fresh CI checkout).

## Configuring your CI

Whatever your CI is, run `php bin/console bab:tested-routes-checker:check` after your tests: it fails if a route has not been tested.

### GitHub Actions

If you're using GitHub Actions, simply add the following step to your existing test job:

```yaml
name: Tests

jobs:
  tests:
    steps:
      # Do your stuff
      # - ...
      # Ensure no new untested route has been introduced
      - name: Run Bab/TestedRoutesCheckerBundle
        run: bin/console bab:tested-routes-checker:check
```

View a fully working example [in `altercampagne/eventoj` repository](https://github.com/altercampagne/eventoj/blob/main/.github/workflows/tests.yml#L71-L72).

If you have several jobs to run all your tests, that's not a problem! 👌

1. Upload an artifact containing all tested routes after each of your job

```yaml
jobs:
  tests:
    steps:
      # Do your stuff
      # - ...
      - name: Save tested routes
        uses: actions/upload-artifact@v4
        with:
          name: tested-routes-${{ inputs.any-relevant-discriminent }}
          path: var/cache/bab_tested_routes_checker_bundle_route_storage
```

2. Run a new job at the end to concatenate all files & run the command

```yaml
jobs:
  tested-routes-checker:
    name: Check tested routes
    needs: [your_tests_job]
    steps:
      # Install the project
      # - ...

      - name: Download All Artifacts
        uses: actions/download-artifact@v4
        with:
          path: tested-routes
          pattern: tested-routes-*

      - name: Create var/cache directory to put tested routes files inside
        shell: bash
        run: mkdir -p var/cache

      - name: Merge all tested routes files
        shell: bash
        run: cat tested-routes/tested-routes-*/bab_tested_routes_checker_bundle_route_storage > var/cache/bab_tested_routes_checker_bundle_route_storage

      - name: Check tested routes
        shell: bash
        run: php bin/console bab:tested-routes-checker:check
```

## History

> [!NOTE]
> This bundle was originally hosted on [Tiime-Software organisation](https://github.com/Tiime-Software/TestedRoutesCheckerBundle). Given the lack of maintenance (see [this PR](https://github.com/Tiime-Software/TestedRoutesCheckerBundle/pull/29) & [this one](https://github.com/Tiime-Software/TestedRoutesCheckerBundle/pull/30)), I decided to create an independent repository in order to give to this project the love it deserves. ♥️
