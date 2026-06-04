# SEO Implementation Spec — Social Network Website

> Feed this file to Cursor. Each section contains specific tasks, file targets, and code patterns to implement.

---

## 1. TECHNICAL SEO — CRAWLABILITY & INDEXING

### 1.1 robots.txt
Create or update `public/robots.txt`:

```
User-agent: *
Allow: /u/          # Public user profiles
Allow: /community/  # Public community/group pages
Allow: /post/       # Public posts
Allow: /blog/       # Blog/resource hub

Disallow: /dashboard/
Disallow: /settings/
Disallow: /notifications/
Disallow: /messages/
Disallow: /api/
Disallow: /admin/

# Allow AI crawlers
User-agent: GPTBot
Allow: /

User-agent: PerplexityBot
Allow: /

User-agent: ClaudeBot
Allow: /

Sitemap: https://yourdomain.com/sitemap.xml
```

### 1.2 Dynamic XML Sitemap
Create a dynamic sitemap endpoint at `/sitemap.xml` (or `/api/sitemap`):

- Include all public user profile URLs: `/u/{username}`
- Include all public community pages: `/community/{slug}`
- Include all public posts: `/post/{id}-{slug}`
- Include all blog articles: `/blog/{slug}`
- Set `<lastmod>` to the page's `updatedAt` timestamp
- Set `<changefreq>` to `weekly` for profiles, `daily` for posts
- Regenerate or cache-bust every 24h

Example structure:
```xml
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <url>
    <loc>https://yourdomain.com/u/johndoe</loc>
    <lastmod>2026-06-01</lastmod>
    <changefreq>weekly</changefreq>
    <priority>0.7</priority>
  </url>
</urlset>
```

### 1.3 Canonical Tags
Add `<link rel="canonical" href="..." />` to every public page to prevent duplicate content. Use the full absolute URL.

---

## 2. META TAGS — DYNAMIC PER PAGE

Implement dynamic `<head>` meta tags. Use a head management library (e.g. `next/head`, `react-helmet`, or equivalent).

### 2.1 User Profile Pages (`/u/{username}`)
```html
<title>{displayName} (@{username}) — {SiteName}</title>
<meta name="description" content="{bio truncated to 155 chars}" />
<meta property="og:title" content="{displayName} on {SiteName}" />
<meta property="og:description" content="{bio}" />
<meta property="og:image" content="{avatarUrl}" />
<meta property="og:url" content="https://yourdomain.com/u/{username}" />
<meta property="og:type" content="profile" />
```

### 2.2 Community/Group Pages (`/community/{slug}`)
```html
<title>{communityName} Community — {SiteName}</title>
<meta name="description" content="{communityDescription truncated to 155 chars}" />
<meta property="og:title" content="{communityName} — {SiteName}" />
<meta property="og:type" content="website" />
```

### 2.3 Post Pages (`/post/{id}`)
```html
<title>{postTitle} — {authorName} on {SiteName}</title>
<meta name="description" content="{postExcerpt truncated to 155 chars}" />
<meta property="og:type" content="article" />
<meta property="article:published_time" content="{createdAt ISO8601}" />
<meta property="article:author" content="{authorName}" />
```

### 2.4 Fallback / Homepage
```html
<title>{SiteName} — {tagline}</title>
<meta name="description" content="Join {SiteName}, the community for {niche}. Connect, share, and discover." />
```

---

## 3. SCHEMA MARKUP (JSON-LD)

Inject `<script type="application/ld+json">` blocks in the `<head>` of each relevant page.

### 3.1 User Profile Page
```json
{
  "@context": "https://schema.org",
  "@type": "Person",
  "name": "{displayName}",
  "url": "https://yourdomain.com/u/{username}",
  "image": "{avatarUrl}",
  "description": "{bio}",
  "sameAs": ["{externalLinkIfAny}"]
}
```

### 3.2 Post / Article Page
```json
{
  "@context": "https://schema.org",
  "@type": "Article",
  "headline": "{postTitle}",
  "author": {
    "@type": "Person",
    "name": "{authorName}",
    "url": "https://yourdomain.com/u/{authorUsername}"
  },
  "datePublished": "{createdAt}",
  "dateModified": "{updatedAt}",
  "image": "{postImageUrl}",
  "url": "https://yourdomain.com/post/{id}"
}
```

### 3.3 Community Page
```json
{
  "@context": "https://schema.org",
  "@type": "Organization",
  "name": "{communityName}",
  "url": "https://yourdomain.com/community/{slug}",
  "description": "{communityDescription}"
}
```

### 3.4 Breadcrumbs (all pages)
```json
{
  "@context": "https://schema.org",
  "@type": "BreadcrumbList",
  "itemListElement": [
    { "@type": "ListItem", "position": 1, "name": "Home", "item": "https://yourdomain.com" },
    { "@type": "ListItem", "position": 2, "name": "{PageName}", "item": "{PageURL}" }
  ]
}
```

---

## 4. URL STRUCTURE

Ensure all public routes follow clean, keyword-rich patterns:

| Page Type       | URL Pattern                          |
|-----------------|--------------------------------------|
| User Profile    | `/u/{username}`                      |
| Community       | `/community/{topic-slug}`            |
| Post            | `/post/{id}-{title-slug}`            |
| Blog Article    | `/blog/{article-slug}`               |
| Tag/Topic Feed  | `/topic/{tag-slug}`                  |

Rules:
- All slugs: lowercase, hyphen-separated, no special chars
- Post URLs include both ID and title slug for stability + readability
- Avoid `/index`, `/page`, query-string-only URLs for public content

---

## 5. PERFORMANCE — CORE WEB VITALS

Target scores: LCP < 2.5s, CLS < 0.1, INP < 200ms.

### 5.1 Images
- Use `loading="lazy"` on all below-the-fold images
- Set explicit `width` and `height` attributes on all `<img>` tags to prevent CLS
- Serve images in WebP/AVIF format
- Use a CDN for user-uploaded media

### 5.2 Fonts
```html
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preload" as="font" type="font/woff2" crossorigin />
```

### 5.3 Critical CSS
- Inline above-the-fold CSS or use a CSS-in-JS solution that avoids render-blocking
- Defer non-critical JS with `defer` or `async`

---

## 6. UGC INDEXING — PUBLIC CONTENT PAGES

### 6.1 Visibility Rules
Only index pages where:
- The post/profile is set to **public**
- The content has a minimum length (e.g. > 50 chars for posts)
- The account is not banned/suspended

Add this logic to your sitemap generator and to a `noindex` meta tag check:
```html
<!-- For private or thin-content pages -->
<meta name="robots" content="noindex, nofollow" />
```

### 6.2 UGC Tag on Links
For all outbound links inside user-generated content, add `rel="ugc nofollow"`:
```html
<a href="{userLink}" rel="ugc nofollow" target="_blank">Link text</a>
```

### 6.3 Content Quality Gate
Before indexing a post, enforce:
- Minimum word count (suggest: 30+ words)
- No spam/duplicate content check at post-creation time
- Profanity/spam filter should also catch SEO-harmful thin content

---

## 7. COMMUNITY & TOPIC PAGES

Each community or topic tag should have a dedicated, SEO-optimized landing page.

Required elements per community page:
- `<h1>` with community name + keyword (e.g. "Photography Community")
- Description paragraph (editable by moderator, min 100 chars)
- List of recent/popular posts (with links)
- Member count, post count (for E-E-A-T signals)
- Proper meta title + description (see Section 2.2)

---

## 8. TRUST & E-E-A-T SIGNALS

Add the following to your site footer and relevant pages:

- [ ] Link to `/about` page explaining the platform's purpose and team
- [ ] Link to `/privacy-policy`
- [ ] Link to `/terms-of-service`
- [ ] HTTPS enforced site-wide (redirect all HTTP → HTTPS)
- [ ] Verified badge system for notable users (add `data-verified` attribute or similar)
- [ ] Contact page or support email visible

---

## 9. INTERNAL LINKING

- Every post page should link back to its author's profile and its community page
- Community pages should link to their top posts
- Blog articles should link to relevant community/topic pages
- User profiles should display and link to their most recent public posts
- Add a "Related Posts" or "More from this community" section on post pages

---

## 10. BLOG / RESOURCE HUB

Create a `/blog` section (if not already present):

- Articles target keywords around your platform's niche
- Each article should be 800–1500 words minimum
- Include internal links to relevant community pages
- Add `Article` schema markup (see Section 3.2)
- Submit new articles to Google via IndexNow API on publish:

```js
// Call on article publish
await fetch('https://api.indexnow.org/indexnow', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    host: 'yourdomain.com',
    key: 'YOUR_INDEXNOW_KEY',
    urlList: [`https://yourdomain.com/blog/${slug}`]
  })
});
```

---

## 11. INDEXNOW — INSTANT INDEXING

Set up IndexNow to ping search engines when new public content is published.

1. Generate a key at https://www.bing.com/indexnow
2. Host the key file at `https://yourdomain.com/{key}.txt`
3. Fire IndexNow ping on:
   - New public post published
   - New community created
   - New blog article published

---

## 12. GOOGLE SEARCH CONSOLE

- [ ] Verify site ownership via DNS TXT record or HTML file
- [ ] Submit sitemap: `https://yourdomain.com/sitemap.xml`
- [ ] Monitor Core Web Vitals report weekly
- [ ] Monitor Coverage report for indexing errors
- [ ] Set up email alerts for manual actions

---

## PRIORITY ORDER FOR IMPLEMENTATION

| # | Task                                      | Impact  | Effort |
|---|-------------------------------------------|---------|--------|
| 1 | Dynamic meta tags (title + description)   | 🔴 High | Low    |
| 2 | robots.txt + sitemap.xml                  | 🔴 High | Low    |
| 3 | Canonical tags                            | 🔴 High | Low    |
| 4 | UGC noindex for private/thin content      | 🔴 High | Medium |
| 5 | Schema markup (Person, Article)           | 🟠 Med  | Medium |
| 6 | Clean URL structure                       | 🟠 Med  | Medium |
| 7 | Core Web Vitals (images, fonts)           | 🟠 Med  | Medium |
| 8 | Community landing pages                   | 🟠 Med  | High   |
| 9 | Internal linking logic                    | 🟡 Low  | Medium |
| 10| IndexNow integration                      | 🟡 Low  | Low    |
| 11| Blog/resource hub                         | 🟡 Low  | High   |
