# Working with AI

Use versioned documentation to give coding assistants current, accurate Elang context. Elang is a PHP framework, not a separate language.

## Documentation entry point

Read `/llms.txt` for a concise index. Every versioned documentation page has a Markdown representation at the same URL with `.md` appended. The HTML head advertises these alternate representations.

## Project conventions

The repository includes AGENTS.md with the architecture, commands, and implementation boundaries. Give coding tools the relevant source files and documentation when asking for changes.

## A useful prompt

```text
Build an Elang PHP controller for a JSON endpoint.
Read the v0.1 routing, validation, and controllers documentation first.
Use Symfony HttpFoundation requests, Elang Response::json, and
Symfony Constraint objects. Do not assume Laravel facades or an ORM.
Write a test for the invalid-input response.
```

## Limits

Publishing documentation does not automatically train a model or guarantee citations in AI search. A dedicated MCP documentation server, semantic search, and editor integration are planned rather than available features. The current search API performs bounded text search over local documentation.
