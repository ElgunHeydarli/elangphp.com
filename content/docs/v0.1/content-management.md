# Manage the documentation website

The official website runs on Elang. Its Content Studio lets a trusted administrator edit documentation, lessons, examples, guides, and update posts without changing application source files.

## Enter the editorial workspace

Open `/login`, sign in with a provisioned administrator account, and complete any required temporary-password change. Then open `/admin`. Normal users cannot enter the workspace, and public registration is disabled on the official site.

Administrator provisioning happens through a trusted terminal, not through a public form. The account must be verified and have the administrator role.

## Draft, preview, and publish

Choose an existing page or create a page in a supported section. Write a title in the page’s selected language, a clear search description, and Markdown beginning with one primary heading. Add prerequisites, complete commands, expected results, failure explanations, and a next step.

Save draft keeps the text private. Preview renders sanitized Markdown without publishing. Publish updates the public page, navigation, search results, Markdown representation, and sitemap from the same published content.

Raw HTML is stripped during Markdown conversion. User-generated scripts do not become executable preview content.

## Revisions and concurrent edits

Each save records a revision and actor. The editor checks the revision number before accepting a change. If another session updated the page, a conflict response asks you to reload rather than silently overwriting that work.

Restoring a historical revision creates a new draft. Review it and publish explicitly. Draft changes do not change the public article's modification date.

## Storage boundaries

Editorial content and revisions live in the private database. The PHP application source and configuration remain outside the web document root. The editor cannot execute shell commands, edit .env, or write arbitrary filesystem paths.

Keep database backups and test restoration procedures. A revision history is useful for editorial recovery but does not replace an operational backup.
