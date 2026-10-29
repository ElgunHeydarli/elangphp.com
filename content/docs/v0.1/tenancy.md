# Organizations, teams, and tenant isolation

A tenant is an organization whose business records are separated from other organizations. Elang uses explicit team identifiers, current membership checks, and SQL transactions. It does not hide tenant identity in a process-wide “current team” variable. This is especially important for queues and long-lived workers that serve many users.

## Prepare tables and create a team

```bash
php elang platform:setup
```

```php
$teams = $app->container->get(Elang\Tenancy\Teams::class);
$team = $teams->create($user, 'Northwind');
$mine = $teams->list($user);
```

The actor must be a current verified account with no temporary password. Creating a team assigns its creator the owner role. Team IDs contain 32 random hexadecimal characters. Their unpredictability is not an authorization boundary: every operation still checks membership.

| Role | Read data | Write data | Invite/remove ordinary members | Assign roles / transfer ownership |
| --- | --- | --- | --- | --- |
| Owner | Yes | Yes | Yes | Yes |
| Admin | Yes | Yes | Yes | No |
| Member | Yes | Yes | No | No |
| Viewer | Yes | No | No | No |

Only an owner appoints or removes administrators. An owner cannot remove or demote themselves: first transfer ownership to an existing member. A transfer changes the former owner to admin and the chosen member to owner in one transaction.

## Scope business queries explicitly

```php
$result = $teams->within(
    $user,
    $teamId,
    'write',
    function (Doctrine\DBAL\Connection $db, array $team) use ($title) {
        $id = bin2hex(random_bytes(16));
        $db->insert('projects', [
            'id' => $id,
            'team_id' => $team['id'],
            'title' => $title,
        ]);
        return $id;
    },
);
```

Create `projects` in your own migration, including a `team_id` foreign key/index. Use bound values. Every read, update, delete, relationship lookup, export, and job must constrain its SQL by the authorized `team_id`. A route parameter or submitted `team_id` must never overwrite that value. Elang's SaaS starter demonstrates this pattern with project records and optimistic versions.

`within()` opens a transaction, locks the team row, reloads membership, and checks the requested permission. Membership changes use the same lock, so revocation and a business write serialize. This conservative design can serialize heavy writes within one team. For larger workloads, profile lock contention and design narrower domain locks while preserving authorization correctness. Do not hold the transaction across email delivery, slow S3 calls, or remote payment requests; use an outbox/saga when crossing systems.

A non-member receives 404; a member with an insufficient role receives 403. This avoids disclosing organization existence to strangers. Cached frontend roles are for presentation only. Server checks decide every operation.

## Invitations

```php
$raw = $teams->invite($owner, $teamId, 'member@example.com', 'member');
$accepted = $teams->accept($signedInVerifiedUser, $raw);
```

Invitation roles are `admin`, `member`, or `viewer`; only the owner can invite an admin. Tokens expire after seven days and are stored only as SHA-256 hashes. Issuing a new invitation for the same team/email replaces the old invitation. Acceptance requires a signed-in verified account whose normalized email matches. The token is consumed once. An existing member's role is not silently elevated by accepting another invitation.

The service returns the raw token once so your application can deliver a private invitation. Do not put it in analytics or logs. Use a no-store, no-referrer acceptance page. The SaaS starter shows a private shareable invitation link. Configure a real mail transport before adding automatic email delivery.

## Membership changes

```php
$teams->role($owner, $teamId, $memberId, 'viewer');
$teams->remove($owner, $teamId, $memberId);
$teams->transfer($owner, $teamId, $nextOwnerId);
```

Queued work should carry team/user/record IDs, reload the actor, and re-enter `within()` at execution time. Membership may have changed since dispatch. For service-owned tasks, define a separate trusted service policy instead of inventing an administrator user.

Test cross-tenant reads and writes, forged IDs, removed members, viewer writes, expired invitations, wrong-email acceptance, concurrent revocation, and ownership transfer. Separate databases, row-level security, encryption boundaries, audit retention, and residency constraints are deployment/domain decisions; this shared-table model does not supply them automatically.

Next: [files](/docs/v0.1/files), [notifications](/docs/v0.1/notifications), and [SaaS reference application](/docs/v0.1/saas).
