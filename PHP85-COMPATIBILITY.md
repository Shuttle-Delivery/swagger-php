# Shuttle PHP 8.5 compatibility backport

This local branch is based on upstream tag `3.3.7`, commit `e8c3bb316e385e93a0c7e8b2aa0681079244c381`. It preserves the existing annotation parser, processors, public API and OpenAPI version used by Shuttle. It is unpublished; do not infer that the upstream Composer distribution contains these changes.

The source changes use the canonical `SplObjectStorage` offset methods in place of the `contains`, `attach` and `detach` aliases deprecated by PHP 8.5, and explicitly declare three already-nullable parameters. The object identity/data behavior and parser logic remain unchanged. Runtime dependency constraints are unchanged. Two existing test assertions use the corresponding PHPUnit 9.6 assertion names.

`Php85CompatibilityTest` converts runtime warnings to exceptions and generates a spec with schemas and duplicate path items. It checks that the existing annotations merge into the expected GET/POST operations and property schema without runtime deprecations. The test failed against the upstream source before the backport.

Validation uses PHPUnit 9.6.34, with an ignored local Composer manifest copied from `composer.json`, `require-dev` limited to that PHPUnit version, and platform PHP 7.4.33. Runtime dependencies consequently work for the PHP 7.4 comparison as well as the PHP 8.5 candidate. No platform requirements are ignored. To reproduce the dependency setup:

```sh
python3 - <<'PY'
import json
from pathlib import Path
manifest = json.loads(Path('composer.json').read_text())
manifest['require-dev'] = {'phpunit/phpunit': '9.6.34'}
manifest['config']['platform'] = {'php': '7.4.33'}
Path('composer.php85-tests.json').write_text(json.dumps(manifest, indent=2) + '\n')
PY
COMPOSER=composer.php85-tests.json composer update --no-scripts --no-plugins
php vendor/phpunit/phpunit/phpunit --configuration phpunit.xml.dist --bootstrap vendor/autoload.php
```

Run with the intended PHP executable. The CLI tests execute `bin/openapi` through its `env php` shebang, so the child `php` must select that same runtime. The isolated Shuttle experiment switches the default executable inside a disposable container for its PHP 7.4 comparison; it does not change the host or running web stack.

Complete source-suite results with PHPUnit 9.6.34:

- PHP 8.5.12: 302 tests / 724,693 assertions, passing.
- PHP 7.4.33: 302 tests / 724,684 assertions, no failures or warnings; two existing PHP-8-only cases skipped.

The PHP 7.4 upstream comparison had the same two skips and two deprecated PHPUnit assertion warnings; the corresponding assertions were renamed without changing their expectations.

Application validation compares the complete generated schema before and after installing this clean committed fork through a mirrored Composer path repository. The local-only fork source must be published and referenced by the normal application lock before it can become a distributable release dependency. Do not edit the application's installed vendor copy manually.
