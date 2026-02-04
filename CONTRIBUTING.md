# Contributing to Hugging Face PHP

Thank you for your interest in contributing to the Hugging Face PHP library! This document provides guidelines and instructions for contributing.

## Getting Started

1. **Fork the repository** on GitHub
2. **Clone your fork** locally:
   ```bash
   git clone https://github.com/codewithkyrian/huggingface-php.git
   cd huggingface-php
   ```
3. **Install dependencies**:
   ```bash
   composer install
   ```
4. **Create a branch** for your changes:
   ```bash
   git checkout -b feature/your-feature-name
   ```

## Development Setup

### Requirements

- PHP 8.2 or higher
- Composer
- A Hugging Face token (for testing authenticated features)

### Running Tests

Use the Composer scripts for the usual workflows:

```bash
# Run unit and feature tests (default; fast, no external API)
composer test

# Run integration tests (hits Hub API; requires token for some tests)
composer test:integration

# Run everything
./vendor/bin/pest

# Run a specific test file
./vendor/bin/pest tests/Integration/Hub/BranchesTest.php

# Run with coverage
./vendor/bin/pest --coverage
```

### Code Style

This project follows PSR-12 coding standards. Before submitting:

```bash
# Check code style
./vendor/bin/php-cs-fixer fix --dry-run

# Fix code style
./vendor/bin/php-cs-fixer fix
```

## Making Changes

### Code Guidelines

1. **Use strict types**: All PHP files should declare `strict_types=1`
2. **Type everything**: Use type hints for parameters, return types, and properties
3. **Immutable builders**: Builder classes should return new instances, not modify `$this`
4. **Readonly DTOs**: Response objects should be `final readonly` classes
5. **Document with PHPDoc**: Add docblocks with `@param`, `@return`, and `@throws`

### Adding New Features

When adding new functionality:

1. **Add tests**: Every new feature needs unit tests
2. **Update documentation**: Add to README.md and docs/API.md

### Commit Messages

Use clear, descriptive commit messages:

```
Add sentence similarity inference method

- Create SentenceSimilarityResponse DTO
- Add sentenceSimilarity() to InferenceClient
- Add unit tests for new functionality
```

## Pull Request Process

1. **Update documentation** if your changes affect the public API
2. **Add tests** for any new functionality
3. **Ensure tests pass**: Run `composer test` (and `composer test:integration` if you changed integration tests) before submitting
4. **Write a clear PR description** explaining what and why
5. **Keep PRs focused**: One feature or fix per PR

### PR Checklist

- [ ] Tests added/updated
- [ ] Documentation updated (if applicable)
- [ ] Code follows PSR-12 style
- [ ] All tests pass
- [ ] Commit messages are clear

## Project Structure

```
src/
├── Exceptions/     # Global exception classes
├── Http/           # HTTP connectors and response handling
├── Hub/            # Hub API implementation
│   ├── Builders/   # Fluent builders for Hub requests
│   ├── DTOs/       # Data Transfer Objects for Hub responses
│   ├── Enums/      # Hub-specific enumerations
│   ├── Exceptions/ # Hub-specific exceptions
│   └── Managers/   # Repository managers
├── Inference/      # Inference API implementation
│   ├── Builders/   # Builders for inference tasks
│   ├── DTOs/       # Inference response objects
│   ├── Enums/      # Inference enumerations
│   ├── Exceptions/ # Inference exceptions
│   └── Providers/  # Logic for different inference providers
└── Support/        # Utilities (Cache, Token, Config)

tests/
├── Unit/           # Unit tests
├── Feature/        # Feature tests
└── Integration/    # Integration tests (real Hub API)

examples/           # Example scripts
docs/               # Additional documentation
```

## Reporting Issues

When reporting bugs:

1. **Use the issue template** if one exists
2. **Include PHP version** and relevant environment details
3. **Provide a minimal reproduction** case
4. **Include error messages** and stack traces

## Questions?

- Open a [GitHub Discussion](https://github.com/codewithkyrian/huggingface-php/discussions)
- Check existing issues for similar questions

## License

By contributing, you agree that your contributions will be licensed under the MIT License.

---

Thank you for contributing! 🎉
