# Build a private user workspace

The dashboard starter provides registration, account settings, persistent private notes, completion state, pagination, and useful counts. Each note belongs to its owner in the database.

## Create the project

```bash
elang new workspace --starter=dashboard
cd workspace
php elang serve
```

Register a personal account, then open `/workspace`. Add a note with a title and details. Mark it complete or remove it using the provided forms.

## Follow one write

The request passes session, CSRF, and authentication middleware. The controller validates the known input fields and sets owner_id from the authenticated user. The repository writes only the model's fillable fields.

Reads, toggles, and deletes are all scoped by owner_id. A supplied body field cannot change that ownership, and a note from another account is returned as not found.

## Check privacy

Create two accounts in separate browser sessions. Add a note in the first, then try its numeric ID from the second. The second account must not read or mutate it. Password changes invalidate other sessions and revoke API tokens.

## Learn from the structure

The Note model demonstrates casts and fillable fields. WorkspaceController demonstrates scoped repositories. The migration demonstrates a foreign key, and the template demonstrates ordinary HTML forms with CSRF tokens. Studio displays query templates without request bodies or SQL bind values.

Configure production mail, HTTPS, private storage permissions, and backups before using the starter for a real shared service.
