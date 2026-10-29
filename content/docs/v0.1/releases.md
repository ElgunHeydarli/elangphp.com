# Packages, upgrades, and release engineering

Elang is a 0.1 development preview. All eighteen development packages are indexed in Packagist as `dev-main`, with the `0.1.x-dev` branch alias. Clean installs of all nine application packages and the global installer have been verified. No stable release or long-term-support commitment is implied.

All eighteen official distribution repositories have active signed GitHub push hooks to Packagist. Updates were verified against Composer development metadata after real pushes; credentials are not present in webhook URLs. If you previously registered the self-hosted source globally, remove that old configuration with `composer global config --unset repositories.elang` before upgrading the Packagist installer.

## Terminal packages

```bash
composer create-project elang/elang app dev-main
composer create-project elang/saas workspace dev-main
```

The distribution includes framework, application skeleton, installer, task-board example, auth/blog/dashboard/API starters, React/Next fullstack starters, SaaS workspace, and an optional Workerman runtime package. Composer may internally download archives; users install through normal terminal commands and dependency resolution.

For the optional global `elang new` command, install the development installer directly from Packagist:

```bash
composer global require elang/installer:dev-main
elang new my-app --starter=saas
```

Add Composer's global bin directory to PATH if `elang` is not found. `composer global config bin-dir --absolute` shows it. See [installation](/docs/v0.1/installation) for operating-system setup. Projects use a normal Composer lockfile; commit it for reproducible application installs, keep `.env` and vendor/runtime files out of Git.

## Build distribution artifacts

Maintainers run:

```bash
php bin/build-packages --output=/absolute/private/build-output --base-url=https://elangphp.com/packages
```

The builder stages explicit framework/source assets and sanitized starter trees, excludes vendor, secrets, databases, runtime data, frontend dependencies, and compiled build directories, then generates Composer metadata with content-addressed archive URLs. Preserve old immutable archives that existing lockfiles reference. Publish new metadata only after testing every package through fresh `composer create-project` or `composer require` against the staged index.

The official website exposes package metadata and allowed archive names from private storage. It never exposes the source checkout or `.env`. Keep package publication separate from an in-progress code checkout so users cannot install an untested intermediate build.

## Packagist publication

Packagist lists one root Composer package per source repository. A multi-package source tree therefore needs reviewed distribution repositories or a documented split workflow. Prepare sanitized package trees, validate their Composer manifests, publish reviewed Git tags, then submit each intended repository through an authorized Packagist account. Configure the GitHub webhook/update integration afterward.

The official `elang/*` namespace and the eighteen distribution repositories are controlled by the project maintainer. Their `dev-main` versions have been verified through Composer metadata and real installations. New names and future releases still require explicit maintainer authorization. Keep account credentials out of source code, URLs, and logs.

## Publish through the administrator panel

The official site provides a protected `/admin/integrations` page. Sign in as a verified administrator, replace any temporary password, and review each prepared repository and source commit before submitting it. Open your [Packagist profile](https://packagist.org/profile/) and copy the **main API token** into that HTTPS form, never into chat or a public issue. Confirm your current account password when saving. The server encrypts the connection in private storage, and the form never displays the token again.

Each “Submit package” button submits only the listed official repository. The publisher refuses to replace a package whose existing source belongs to another repository. Credentials travel in an Authorization header rather than a URL. A successful API response means **submitted for indexing**; confirm the development version appears in Packagist before changing public installation instructions. Configure automatic GitHub update hooks in Packagist after the initial submission.

Maintainers can prepare sanitized source trees with:

```bash
php bin/export-packages --output=/absolute/private/release-trees
```

Packagist resolves `dev-main` and its `0.1.x-dev` alias. The self-hosted repository at `https://elangphp.com/packages` remains an explicit alternative for `0.1.0-dev` snapshots; do not mix that snapshot label with Packagist branch versions. Exported Packagist manifests contain no private build repository. Never include tokens, runtime data, or application keys in a split repository.

## Compatibility policy

During `0.x` development, review every change as potentially breaking. No LTS/security-support duration has been announced. A stable release requires a documented public API, upgrade guide, supported PHP/database/runtime matrix, sustained CI, dependency/security review, repeatable artifacts, rollback rehearsals, and a clear vulnerability-reporting policy.

CI checks PHP 8.3/8.4, disposable SQL databases, Redis behavior, static analysis, dependency advisories, and frontend contracts/builds. Minimum-dependency and current-dependency resolution should be tested in isolated jobs; a lockfile-only run does not cover every allowed Composer constraint. Frontend lockfiles and runtime requirements are checked separately.

## Upgrade an application safely

1. Read versioned docs, release notes, changed schemas, and any public API changes.
2. Back up SQL, object storage where required, and encryption keys; prove restoration in an isolated environment.
3. Update in a branch, review Composer/npm lockfile changes, and run the project's own tests.
4. Rehearse migrations against a restored copy; `platform:setup` is additive setup, not a substitute for a reviewed deployment migration plan.
5. Deploy an immutable release, recycle queue/socket workers, and monitor error rate, queue lag, and outbox backlog.
6. Keep a rollback release and a database rollback/forward-repair decision. Reverting source does not undo a schema or external side effect.

Never rotate `APP_KEY` as a routine upgrade: MFA secrets, private development mail, queued encrypted messages, signed file URLs, and audit verification depend on it. Plan key rotation as a separate migration with an explicit recovery strategy.

## Verify claims with evidence

Use authenticated database-backed API workloads, realistic payload sizes, competing tenants, queued side effects, and real-time fan-out when evaluating performance. Record hardware, PHP/opcache settings, database version, concurrency, warmup, duration, errors, and latency distribution. An empty “hello world” benchmark cannot establish banking readiness or superiority over every framework.

See [testing](/docs/v0.1/testing), [deployment](/docs/v0.1/deployment), and the repository's validation report for the exact checks performed on a release.
