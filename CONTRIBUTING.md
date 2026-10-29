# Contributing to Elang

Start with a focused improvement: a reproducible bug, a clearer explanation, or a tested behavior change. Elang is a development preview, so propose substantial API changes in an issue before building a large change.

## Local setup

Run composer install, copy .env.example to .env, generate an application key, and run the migration and development server as described in README.md.

## Code conventions

Use PHP 8.3-compatible code, strict types, PSR-4 namespaces, explicit dependencies, and constructor injection. Framework code belongs in src; site-specific behavior belongs in app. Keep the frontend HTML, CSS, and vanilla JavaScript.

Document the final behavior with an English example. Do not advertise planned features as available. Add tests for meaningful behavioral changes, especially validation, middleware ordering, errors, and data handling.

## Before opening a pull request

Run composer validate --strict, composer test, composer analyse, and composer audit. Explain the concrete problem, the resulting behavior, and relevant checks. Never include .env, keys, credentials, vendor files, databases, or runtime logs.

## Security concerns

Read SECURITY.md before reporting vulnerabilities. Do not publish exploit details or real credentials in public issues.
