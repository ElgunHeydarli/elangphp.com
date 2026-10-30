# Test behavior before deployment

Prerequisite: the previous two practical lessons. Goal: verify JSON, database persistence, private-field exclusion and the external API adapter through the real kernel.

Run the catalogue example, then read its TestClient assertions. Introduce a wrong JSON value to see a failure and restore it. Create a separate application test using the application-testing chapter. Add one authorization-denied case and one CSRF failure before deployment.

```bash
php vendor/elang/framework/examples/orm-catalog/run.php
```

You are done when the positive and negative cases pass against an isolated database, with no email or external request sent. Framework tests do not replace tests for your own permissions and business rules.

[Next step](/docs/v0.1/application-testing)
