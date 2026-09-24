# Design and interaction contract

## Reference fidelity
reference-site/ contains actual source from the existing three-page site: index.html, blog/index.html, projects/index.html, styles.css and motion.js. Read these before authoring. Serve that directory as an HTTP root for comparison (absolute /styles.css paths do not work reliably via file://). Never require access to the existing private deployed URL.

Retain the cobalt #152aee, near-white #f3f4f4, dark #15161a, gray copy, lime #caff75 accent; oversized sans-serif headlines with italic serif emphasis; fine borders, generous whitespace and small mono metadata. Keep Hasan branding. Use system fonts initially; no dependency on external font CDNs. Empty blog/project screens should resemble the reference. Populated lists and detail pages extend it instead of using generic dashboard cards.

Port to reusable React components and scoped tokens. Reference CSS is a visual starting point, not a requirement to retain every selector or issue. Restrict public styles to frontend layout so Payload admin controls are not affected. Public body text at least 16px; functional labels 14px; secondary metadata 12px. Do not retain mobile reference rules that make paragraph copy too small.

## Layout
Home fits the target normal-zoom viewports in PROJECT_SPEC.md. Other pages grow naturally. Header/footer shared across public routes; active navigation is semantic aria-current. On mobile use compact visible Home/Blog/Projects navigation; no unnecessary hamburger. Preserve keyboard focus and make touch targets about 44px high. Intro and tags come from CMS with bounded length. Do not make noninteractive tags look clickable.

## Motion
Port the intent of reference motion.js, not its raw location.assign interception into Next router navigation. Prefer built-in routing and a small isolated animation component. Use CSS transitions/WAAPI; add a lightweight animation dependency only if needed and justified. Do not rely on experimental Next configuration merely to animate navigation.

- First entry: soft opacity + 10–14px rise, 450–650ms, short stagger 40–80ms.
- Route change: main content transition about 200–400ms; avoid blocking navigation on network fetch or decorative animations.
- Hover: underline expands, link arrows move 2–3px, tiny lift on actionable cards.
- Press: scale around .97, 100–160ms; return reliably on pointer cancellation.
- Reduced motion: eliminate travel/rotation/stagger and use immediate or very short opacity changes.
- No infinite spinning, parallax, cursor replacement, scroll hijacking or page-wide click ripples.
- Back/forward, modified click, new-tab, hash links and downloads must behave normally.
- Keyboard route navigation moves focus to main heading and announces the page appropriately; loading/error states cannot strand focus.
- JavaScript failure must not leave CSS opacity at zero. Never hide the whole page waiting for hydration.

## Detail layouts
Articles: readable measure about 65–75 characters, clear h2/h3, code blocks scroll horizontally inside themselves, accessible copy-code feedback, images reserve aspect ratio. Projects: intro + technologies + optional action links + case study + image gallery. Avoid fabricated outcome metrics. Lists show clear card links, publication metadata and optional cover; no broken empty image placeholders.

## Visual verification
Capture 1440x900, 1280x720, 390x844, 375x667 with empty and populated collections; exercise 200% zoom and reduced motion. Ensure animations do not create horizontal page scrolling. Store screenshots under an ignored artifacts directory and summarize checks in VERIFICATION.md. Correct source problems before completion.
