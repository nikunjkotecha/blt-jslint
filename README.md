Acquia BLT PHP_CodeSniffer
====

This is an [Acquia BLT](https://github.com/acquia/blt) plugin providing integration with [PHP_CodeSniffer](https://github.com/squizlabs/PHP_CodeSniffer) and [Acquia’s Coding Standards](https://github.com/acquia/coding-standards-php).

This plugin provides command `validate:jslint` that use ESLint to check for coding standard violations.

It also hooks into
* `source:build:frontend-reqs` to install eslint and configure defaults
* `internal:git-hook:execute:pre-commit` to validate all the JS files in commit

This plugin is **community-supported**. Acquia does not provide any direct support for this software or provide any warranty as to its stability.

## Installation and usage

To use this plugin, you must already have a Drupal project using BLT 12 or higher.

Add the following to the `repositories` section of your project's composer.json:

```
"blt-jslint": {
    "type": "vcs",
    "url": "https://github.com/nikunjkotecha/blt-jslint.git",
    "no-api": true
}
```

or run:

```
composer config repositories.blt-jslint '{"type": "vcs", "url": "https://github.com/nikunjkotecha/blt-jslint.git", "no-api": true}'
```

In your project, require the plugin with Composer:

`composer require --dev nikunjkotecha/blt-jslint`

Update your projects blt.yml to enable the validations

Add `js-lint: true` in blt.yml file of your project.

Run `blt frontend:setup` once after enabling to ensure eslint is installed.

# License

Copyright (C) 2022 Acquia, Inc.

This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License version 2 as published by the Free Software Foundation.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more details.
