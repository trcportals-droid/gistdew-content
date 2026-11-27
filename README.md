# GistDew content feed

This repository feeds **gistdew.com**. The *GistDew Autopost* WordPress plugin reads `posts.json` every hour and imports any post it hasn't seen before, with its cover image and category.

Feed URL for the plugin:

```
https://raw.githubusercontent.com/trcportals-droid/gistdew-content/main/posts.json
```

## How a daily update works

1. Write the day's posts as Markdown in a new folder, for example `source/2026-11-28/posts.md`, using the format below.
2. Build them into the feed (this renders the HTML, makes the branded cover images and merges them into `posts.json`):

   ```
   pip install markdown pillow playwright   # once
   python3 tools/build_content.py source/2026-11-28 . --fonts tools/fonts --merge --step-hours 2
   ```

3. Commit and push. The site picks the new posts up within the hour.

`posts.json` keeps the 60 most recent posts. Older posts stay on the website; they just drop out of the feed.

## Post format

Each post starts with `=== POST ===`, then a few header lines, then `---`, then the article in Markdown.

```
=== POST ===
title: Headline in sentence case
slug: short-url-friendly-slug
category: Japa & Visas
tags: UK, visas
excerpt: One or two sentences shown on the home page and in Google.
sources: Source title|https://example.com/page; Another source|https://example.org/
photo: London street rain
---
**Quick gist:** The two-line summary. It becomes the highlighted box at the top.

## A subheading

Body text…
```

**Photo:** a few plain words describing a stock photo (no celebrity names). The site finds a free, commercially licensed photo on Openverse and credits the photographer under the image. Leave it out to use the generated cover card instead.

**Categories:** Japa & Visas · Money · Naija News · Entertainment · Diaspora Life · Jobs & Careers · Sports

## Rules for every post

- Original writing only. Never copy another site's article, and never use photos from news sites. Photos come from Openverse (CC0, public domain or CC BY only) and are credited; the generated cover card is the fallback.
- Every fact comes from a source listed in `sources:`. Prefer official sources (GOV.UK, IRCC, CBN, travel.state.gov) for rules and figures.
- No made-up quotes, and nothing defamatory. When a story names private people accused of wrongdoing, leave their names out.
- Immigration and money posts end with a one-line "general information, not advice" note.

## Daily email (digest.json)

Each morning the writing agent also overwrites `digest.json`:

```json
{"date": "2026-11-28", "subject": "Under 60 characters", "preview": "Under 120 characters", "intro": "2–4 friendly sentences, plain text.", "signoff": "One short closing line."}
```

At 08:30 UK time the GistDew Autopost plugin builds an email from the last 24 hours of posts, uses this copy if `date` is today (otherwise a plain default), and schedules it in SendFox to the GistDew Daily Digest list.
