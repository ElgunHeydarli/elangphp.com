# Changelog

## 0.1.0-dev — 2026-10-29

Initial development snapshot. Includes the application kernel, explicit routing/container API, modular services, CLI generators, and an English documentation website built on Elang with an HTML/CSS/JavaScript frontend.

This is not a stable release. See ROADMAP.md for unfinished areas and README.md for supported behavior.

### Learning and distribution expansion

Added a ten-lesson beginner path, setup/doctor guidance, a glossary, troubleshooting, FAQ, practical guides, and a complete session-backed task board. Added semantic metadata, JSON-LD, canonical redirects, sitemap modification dates, conditional HTML responses, social previews, and browser-local lesson completion.

Added a private-artifact-backed public Composer repository for the framework, application skeleton, installer, and task-board project. The global installer provides `elang new`; Composer create-project installs dependencies, generates the key, and runs doctor. Public installation does not require manually downloading or extracting source archives.

Added a narrow HTTP benchmark harness with a Laravel 13 fixture and explicit workload limits. It is not a claim of whole-framework or production superiority.
