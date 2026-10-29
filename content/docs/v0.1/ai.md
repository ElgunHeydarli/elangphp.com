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

Publishing documentation does not automatically train a model or guarantee citations in AI search. The read-only MCP documentation server is available at /mcp and through php elang docs:serve. Semantic search and dedicated editor extensions are future work. The current search API performs bounded text search over local documentation.

## Connect the documentation server

The public read-only Streamable HTTP endpoint is `https://elangphp.com/mcp`. It exposes search_docs and read_doc plus listed elang:// Markdown resources. No account token is needed for public documentation. It rejects cross-origin browser requests and offers no write or execution tools.

For a local stdio client, run `php elang docs:serve` from the documentation website checkout. Standard output contains JSON-RPC messages only. This implementation supports MCP protocol 2025-11-25.
