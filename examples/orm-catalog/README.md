# Disposable ORM catalogue

From a framework checkout, run `composer install`, then `php examples/orm-catalog/run.php`.
From an application with `doctrine/orm:^3.7` installed, run
`php vendor/elang/framework/examples/orm-catalog/run.php`.

Expected JSON: `{"items":2,"total_quantity":8,"partner_quantity":12,"checks":"passed"}`.
The example creates and removes its own private temporary directory, uses SQLite in memory,
and fakes the partner API. It does not load your application bootstrap, use your database,
send mail, or contact the partner. It demonstrates a fetch join, explicit resource fields,
collections and kernel assertions together. Its fixture data is public demonstration data.

Use the protected API starter and ownership checks for real customer records and writes.
Use reviewed migrations for a persistent database; never copy this SchemaTool setup into a
production request. Read the ORM, HTTP client, resources and application-testing chapters.
