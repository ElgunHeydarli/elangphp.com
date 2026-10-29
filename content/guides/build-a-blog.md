# Build a database-backed blog with Elang

The blog starter combines authentication, verified publishing, a Post model, pagination, ownership checks, and escaped server-rendered HTML. It is a complete working example rather than a set of disconnected snippets.

## Create the project

```bash
elang new journal --starter=blog
cd journal
php elang serve
```

Composer installs dependencies, creates the application key, applies the authentication and posts migrations, and runs doctor. Open the local URL shown by the server.

## Create and verify your account

Register at `/register`. In local mode, open `/_elang/studio/mail` and use the verification link. The mailbox stores encrypted local messages and is available only from loopback in a local environment.

After verification, open `/posts/new`. Write a title and article text. Save as a draft first, then select Publish when you want it listed publicly.

## Understand the files

Post declares the table, fillable fields, and casts. BlogController validates input, sets author_id from the current user, and scopes update and delete by that same identifier. The posts migration creates the schema and author foreign key. The Twig view escapes article text rather than treating it as arbitrary HTML.

Public readers see only published posts. A second account cannot edit or delete the first account's article. Knowing a numeric ID is not authorization.

## Verify the result

Create an article, edit it, publish it, and open its public URL. Create a second account and confirm the first article's edit URL returns 404. Add several articles to see pagination. Inspect Studio to understand the queries behind the page.

Before deployment, configure HTTPS, a real mail transport, database credentials, and production environment settings. Review the deployment and authentication guides for the boundaries that a public application must preserve.
