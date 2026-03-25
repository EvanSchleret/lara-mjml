# Contributing

## Prerequisites

- PHP `>=8.2`
- Composer

## Local setup

```bash
composer install
```

## Development workflow

1. Create a branch from `main`.
2. Keep changes focused and deterministic.
3. Add or update tests with your changes.
4. Run the full test suite before opening a PR.

## Run tests

```bash
composer test
```

## Coding guidelines

- Follow the existing code style and structure.
- Use strict typing in PHP (`declare(strict_types=1);`).
- Prefer small, composable classes/functions.
- Avoid adding dependencies unless necessary.
- Keep public behavior deterministic.

## Pull requests

Please include:

- A short summary of the change
- Why the change is needed
- Test coverage details (new/updated tests)
- Any breaking changes or migration notes

Thank you for contributing.
