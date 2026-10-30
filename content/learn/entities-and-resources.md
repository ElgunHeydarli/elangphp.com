# Build an entity-backed JSON response

Prerequisite: database lesson and migrations. Goal: combine real entity relationships, an explicit JSON resource and a collection calculation.

Run the catalogue below. Open its Entities and ProductResource source files. The query fetch-joins categories; the resource excludes the internal note. Change the two fixture quantities and the expected total together, then rerun.

```bash
php vendor/elang/framework/examples/orm-catalog/run.php
```

You are done when the result is correct and no private field appears in the API output. For a persistent application, write a reviewed migration and add authentication and ownership constraints before handling writes.

[Next step](/docs/v0.1/orm)
