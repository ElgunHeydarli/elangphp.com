# Security policy

Elang 0.1 is a development preview. It has not undergone an independent security audit, has no production support guarantee, and may receive breaking changes.

## Reporting a vulnerability

Do not put exploit details, credentials, private data, or encryption keys in a public issue. If private vulnerability reporting is enabled for the GitHub repository, use that channel. Otherwise, use contact details provided by the maintainer on their GitHub profile to arrange a private channel before sending sensitive details.

Provide the affected revision, PHP version, minimal reproduction, impact, and a suggested fix when possible. There is no published response-time commitment yet.

## Deployment expectations

Expose only public/. Keep configuration, source, vendor files, SQLite databases, runtime logs, and Git metadata private. Disable debug output in production, configure HTTPS, review dependency advisories, and use application-specific authentication and authorization. The framework's primitives do not replace those controls.
