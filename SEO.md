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

Server-rendered English, Azerbaijani, and Russian HTML; one primary H1; descriptive URLs and links; self-referencing canonical URLs without tracking parameters; trailing-slash 301 redirects; real 404/405 responses; JSON-LD WebSite, SoftwareSourceCode, TechArticle, and BreadcrumbList as appropriate; social metadata; XML sitemap with actual content modification dates; a release feed; clean Markdown alternatives; noindex on APIs, Markdown duplicates, demos, and Composer repository responses; responsive layouts, reduced-motion support, and same-origin frontend assets.

No fake ratings, testimonials, FAQ rich-result promises, or unsupported framework claims are added. The visible FAQ answers user questions, while structured data describes what is actually present.

## Verification and operations

Run composer test and composer analyse. SEO tests inspect canonical pages, metadata uniqueness, JSON-LD validity, sitemap coverage, noindex responses, conditional HTTP responses, and redirect behavior. Browser checks exercise search, navigation, mobile layouts, progress tracking, and the complete task-board workflow.

On 29 October 2026, ownership of `https://elangphp.com/` was verified in Google Search Console using the production HTML meta tag. Google successfully fetched `/sitemap.xml` and discovered 213 canonical URLs. Discovery and submission do not mean every URL is already indexed; the new property reports that search data is still processing.

The Elang Google Analytics account is `414019283`, GA4 property `561448619`, web stream `16227731399`, measurement ID `G-TK07WZRNE1`. A real consented browser visit appeared in the real-time report with `page_view`, `first_visit`, and `session_start`. Public identifiers are configured in the private production environment; empty defaults disable integration in source checkouts.

Google Analytics loads only after visitor permission on public site pages. Account, administrator, demo, API and error responses do not receive the bootstrap. Query strings, fragments, form values, search terms and account identifiers are not sent; referrers are limited to their origin. Advertising personalization, Google signals and enhanced measurement are disabled. Visitors can withdraw through the localized footer control; GPC and DNT are respected. The localized privacy page explains these choices.

Keep the ownership meta tag and published sitemap available. Use actual Search Console query and page data to improve content after Google finishes processing; no search-position guarantee is made.

Useful primary guidance: https://developers.google.com/search/docs/fundamentals/seo-starter-guide and https://developers.google.com/search/docs/appearance/structured-data/breadcrumb . Schema types: https://schema.org/SoftwareSourceCode and https://schema.org/TechArticle .

The verified Search Console property is linked to the matching Elang GA4 web stream. Homepage URL inspection confirms that Google has indexed the root URL; the sitemap's 213 discovered pages are not all claimed indexed.
