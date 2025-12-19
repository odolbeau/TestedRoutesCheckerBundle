# TestedRoutesCheckerBundle

A bundle to ensure all routes of a Symfony application have been tested.

> [!NOTE]
> This bundle was originally hosted on [Tiime-Software organisation](https://github.com/Tiime-Software/TestedRoutesCheckerBundle). Given the lack of maintenance (see [this PR](https://github.com/Tiime-Software/TestedRoutesCheckerBundle/pull/29) & [this one](https://github.com/Tiime-Software/TestedRoutesCheckerBundle/pull/30)), I decided to create an independant repository in order to give to this project the love it deserves. ♥️

## How it works?

1. Launch your tests using PHPUnit or anything else. All called routes will be stored in `var/cache/bab_tested_routes_checker_bundle_route_storage`.
2. Run `php bin/console bab:tested-routes-checker:check` to have a small report of what's tested and what's not!

## Installation

Make sure Composer is installed globally, as explained in the [installation
chapter](https://getcomposer.org/doc/00-intro.md) of the Composer
documentation.

### Applications that use Symfony Flex

Open a command console, enter your project directory and execute:

```console
composer require bab/tested-routes-checker-bundle
```

### Applications that don't use Symfony Flex

#### Step 1: Download the Bundle

Open a command console, enter your project directory and execute the
following command to download the latest stable version of this bundle:

```console
composer require bab/tested-routes-checker-bundle
```

#### Step 2: Enable the Bundle

Then, enable the bundle by adding it to the list of registered bundles
in the `config/bundles.php` file of your project:

```php
// config/bundles.php

return [
    // ...
    Bab\TestedRoutesCheckerBundle\BabTestedRoutesCheckerBundle::class => ['dev' => true, 'test' => true],
];
```

## Using baseline to ignore some routes

You can ignore some routes with a `.bab-trc-baseline` file with 1 route per line.
