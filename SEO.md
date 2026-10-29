# Search and learning architecture

The website targets English readers looking to understand PHP frameworks, learn Elang, install it, and solve specific development problems. No search-volume, ranking, traffic, customer, or review metrics are invented.

## Content responsibilities

- Home: identify Elang as a PHP framework and direct readers to installation or the learning path.
- Documentation: versioned API and behavior references, prerequisites, troubleshooting, FAQ, and glossary.
- Learn: ten ordered lessons, expected results, exercises, and a complete runnable project.
- Guides: conceptual framework explanation, a candid Laravel comparison, and a complete JSON API walkthrough.
- Examples: short focused patterns and the live backend playground.
- Releases and roadmap: distinguish available behavior from planned work.

Each intent has a primary page. References and tutorials link to one another rather than multiplying near-identical pages for keyword variants. Titles and descriptions are unique; metadata tests cover every canonical public page.

## Technical behavior

Server-rendered English HTML; one primary H1; descriptive URLs and links; self-referencing canonical URLs without tracking parameters; trailing-slash 301 redirects; real 404/405 responses; JSON-LD WebSite, SoftwareSourceCode, TechArticle, and BreadcrumbList as appropriate; social metadata; XML sitemap with actual content modification dates; a release feed; clean Markdown alternatives; noindex on APIs, Markdown duplicates, demos, and Composer repository responses; responsive layouts, reduced-motion support, and same-origin frontend assets.

No fake ratings, testimonials, FAQ rich-result promises, or unsupported framework claims are added. The visible FAQ answers user questions, while structured data describes what is actually present.

## Verification and operations

Run composer test and composer analyse. SEO tests inspect canonical pages, metadata uniqueness, JSON-LD validity, sitemap coverage, noindex responses, conditional HTTP responses, and redirect behavior. Browser checks exercise search, navigation, mobile layouts, progress tracking, and the complete task-board workflow.

The domain currently needs its DNS to point to the application origin and a valid HTTPS certificate before public discovery and terminal installation can work. After that, verify ownership in Search Console, submit /sitemap.xml, inspect representative URLs, and use real query data to improve confusing or underperforming pages. No Search Console ownership or indexing success is claimed without confirmation.

Useful primary guidance: https://developers.google.com/search/docs/fundamentals/seo-starter-guide and https://developers.google.com/search/docs/appearance/structured-data/breadcrumb . Schema types: https://schema.org/SoftwareSourceCode and https://schema.org/TechArticle .
