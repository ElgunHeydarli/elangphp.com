# Security policy

Elang 0.1 is a development preview. It has not undergone an independent security audit, has no production support guarantee, and may receive breaking changes.

## Reporting a vulnerability

Do not put exploit details, credentials, private data, or encryption keys in a public issue. Private vulnerability reporting is enabled on the canonical repository. Use [Report a vulnerability](https://github.com/ElgunHeydarli/elangphp.com/security/advisories/new) to contact maintainers privately; do not open a public issue with exploit details.

Provide the affected revision, PHP version, minimal reproduction, impact, and a suggested fix when possible. There is no published response-time commitment yet.

The official domain advertises this reporting route in [`/.well-known/security.txt`](https://elangphp.com/.well-known/security.txt), following [RFC 9116](https://www.rfc-editor.org/rfc/rfc9116.html). Review its contact links and renew its expiry before 29 April 2027.

## Deployment expectations

Expose only public/. Keep configuration, source, vendor files, SQLite databases, runtime logs, and Git metadata private. Disable debug output in production, configure HTTPS, review dependency advisories, and use application-specific authentication and authorization. The framework's primitives do not replace those controls.
