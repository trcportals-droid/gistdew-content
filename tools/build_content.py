"""Build GistDew content packs from markdown posts.

Reads posts/*.md (blocks separated by '=== POST ==='), renders HTML, generates
branded cover images with Playwright, and writes:
    <out>/posts.json
    <out>/images/<slug>.jpg

Usage: python3 build_content.py <posts_dir> <out_dir> [--order slug1,slug2,...] [--start ISO] [--step-hours N]
"""
import argparse
import datetime as dt
import html
import io
import json
import os
import re
import sys

import markdown
from PIL import Image

CAT_COLORS = {
    "Japa & Visas": ("#0e7a55", "#063d2b"),
    "Money": ("#c2410c", "#5c1d05"),
    "Jobs & Careers": ("#1d4ed8", "#0c2366"),
    "Diaspora Life": ("#7c3aed", "#36176b"),
    "Entertainment": ("#db2777", "#5f0f33"),
    "Sports": ("#0891b2", "#063e4c"),
    "Naija News": ("#b91c1c", "#4f0b0b"),
}
CAT_SLUGS = {
    "Japa & Visas": "japa-visas", "Money": "money", "Jobs & Careers": "jobs-careers",
    "Diaspora Life": "diaspora-life", "Entertainment": "entertainment", "Sports": "sports",
    "Naija News": "naija-news",
}


def parse_file(path):
    text = open(path, encoding="utf-8").read()
    posts = []
    for block in text.split("=== POST ===")[1:]:
        head, body = block.split("\n---\n", 1)
        meta = {}
        for line in head.strip().splitlines():
            if ":" in line:
                k, v = line.split(":", 1)
                meta[k.strip()] = v.strip()
        sources = []
        for s in filter(None, [x.strip() for x in meta.get("sources", "").split("; ")]):
            if "|" in s:
                t, u = s.rsplit("|", 1)
                sources.append({"title": t.strip(), "url": u.strip()})
        posts.append({
            "title": meta["title"], "slug": meta["slug"], "category": meta["category"],
            "tags": [t.strip() for t in meta.get("tags", "").split(",") if t.strip()],
            "excerpt": meta.get("excerpt", ""), "sources": sources, "body": body.strip(),
            "photo_query": meta.get("photo", ""),
        })
    return posts


def render_html(post):
    md = markdown.Markdown(extensions=["tables", "sane_lists"])
    out = md.convert(post["body"])
    # Turn the opening "Quick gist" paragraph into a highlighted box.
    out = re.sub(r'^<p><strong>Quick gist:</strong>(.*?)</p>',
                 r'<div class="gist-box"><p><strong>Quick gist:</strong>\1</p></div>', out, count=1, flags=re.S)
    # Open external links in a new tab.
    out = re.sub(r'<a href="(https?://)', r'<a target="_blank" rel="noopener" href="\1', out)
    if post["sources"]:
        items = "".join(
            f'<li><a href="{html.escape(s["url"])}" target="_blank" rel="noopener nofollow">{html.escape(s["title"])}</a></li>'
            for s in post["sources"])
        out += f'\n<div class="sources"><h2>Sources</h2><ul>{items}</ul></div>'
    return out


COVER_TMPL = """<!doctype html><html><head><meta charset="utf-8"><style>
@font-face{font-family:BG;src:url(file://%(fonts)s/bricolage-grotesque-latin-800-normal.woff2)}
@font-face{font-family:BG7;src:url(file://%(fonts)s/bricolage-grotesque-latin-700-normal.woff2)}
@font-face{font-family:FT;src:url(file://%(fonts)s/figtree-latin-700-normal.woff2)}
html,body{margin:0}
#c{width:1200px;height:675px;position:relative;overflow:hidden;color:#fff;
 background:radial-gradient(900px 600px at 85%% -10%%,%(c1)s 0%%,transparent 70%%),linear-gradient(135deg,%(c1)s 0%%,%(c2)s 100%%)}
.drop{position:absolute;border-radius:50%% 50%% 50%% 50%% / 60%% 60%% 40%% 40%%;background:rgba(255,255,255,.08)}
.d1{width:420px;height:520px;right:-110px;top:-90px;transform:rotate(18deg)}
.d2{width:220px;height:270px;right:250px;bottom:-140px;transform:rotate(-12deg);background:rgba(255,255,255,.06)}
.d3{width:70px;height:70px;border-radius:50%%;background:#ff6b3d;right:150px;top:110px;opacity:.95}
.grain{position:absolute;inset:0;background-image:radial-gradient(rgba(255,255,255,.07) 1px,transparent 1px);background-size:6px 6px}
.inner{position:absolute;inset:0;padding:64px 72px;display:flex;flex-direction:column}
.pill{align-self:flex-start;font:700 22px/1 FT;letter-spacing:.12em;text-transform:uppercase;background:rgba(0,0,0,.25);padding:12px 18px;border-radius:10px;border:2px solid rgba(255,255,255,.35)}
h1{font-family:BG;font-size:%(fs)spx;line-height:1.06;letter-spacing:-.02em;margin:auto 0 0;max-width:960px;text-shadow:0 4px 30px rgba(0,0,0,.25)}
.foot{display:flex;align-items:center;justify-content:space-between;margin-top:34px;font:700 24px/1 FT}
.logo{display:flex;align-items:center;gap:12px;font:30px/1 BG7;letter-spacing:-.02em}
.logo svg{width:40px;height:40px}
.url{opacity:.8;font-size:22px}
</style></head><body><div id="c"><div class="grain"></div><div class="drop d1"></div><div class="drop d2"></div><div class="d3 drop"></div>
<div class="inner"><span class="pill">%(cat)s</span><h1>%(title)s</h1>
<div class="foot"><span class="logo"><svg viewBox="0 0 40 40"><path d="M20 3C20 3 7 17.5 7 25.5a13 13 0 0 0 26 0C33 17.5 20 3 20 3z" fill="#fff"/><circle cx="27" cy="11" r="3.2" fill="#ff6b3d"/></svg>GistDew</span><span class="url">gistdew.com</span></div>
</div></div></body></html>"""


def font_size(title):
    n = len(title)
    if n <= 45:
        return 76
    if n <= 70:
        return 64
    if n <= 95:
        return 56
    return 48


def make_covers(posts, img_dir, fonts_dir, tmp_dir):
    from playwright.sync_api import sync_playwright
    os.makedirs(img_dir, exist_ok=True)
    with sync_playwright() as p:
        browser = p.chromium.launch()
        page = browser.new_page(viewport={"width": 1200, "height": 675})
        for post in posts:
            c1, c2 = CAT_COLORS.get(post["category"], ("#0e7a55", "#063d2b"))
            doc = COVER_TMPL % {
                "fonts": fonts_dir, "c1": c1, "c2": c2, "cat": html.escape(post["category"]),
                "title": html.escape(post.get("cover_title") or post["title"]), "fs": font_size(post["title"]),
            }
            f = os.path.join(tmp_dir, "cover.html")
            open(f, "w", encoding="utf-8").write(doc)
            page.goto("file://" + f)
            page.wait_for_timeout(150)
            png = page.locator("#c").screenshot()
            Image.open(io.BytesIO(png)).convert("RGB").save(
                os.path.join(img_dir, post["slug"] + ".jpg"), "JPEG", quality=84, optimize=True, progressive=True)
        browser.close()


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("posts_dir")
    ap.add_argument("out_dir")
    ap.add_argument("--fonts", required=True)
    ap.add_argument("--order", default="")
    ap.add_argument("--start", default="")
    ap.add_argument("--step-hours", type=float, default=7)
    ap.add_argument("--merge", action="store_true", help="append to an existing posts.json instead of replacing it")
    a = ap.parse_args()
    a.out_dir = os.path.abspath(a.out_dir)
    a.fonts = os.path.abspath(a.fonts)

    posts = []
    for name in sorted(os.listdir(a.posts_dir)):
        if name.endswith(".md"):
            posts += parse_file(os.path.join(a.posts_dir, name))
    if a.order:
        rank = {s: i for i, s in enumerate(a.order.split(","))}
        posts.sort(key=lambda p: rank.get(p["slug"], 999))

    start = dt.datetime.fromisoformat(a.start) if a.start else dt.datetime.now(dt.timezone.utc).replace(microsecond=0)
    for i, p in enumerate(posts):
        p["date"] = (start - dt.timedelta(hours=a.step_hours * i)).isoformat()
        p["content"] = render_html(p)
        p["image"] = "images/" + p["slug"] + ".jpg"
        p["image_alt"] = p["title"]
        p["category_slug"] = CAT_SLUGS.get(p["category"], "")
        p["id"] = p["slug"]

    tmp = os.path.join(a.out_dir, ".tmp")
    os.makedirs(tmp, exist_ok=True)
    make_covers(posts, os.path.join(a.out_dir, "images"), a.fonts, tmp)
    os.remove(os.path.join(tmp, "cover.html"))
    os.rmdir(tmp)

    feed_path = os.path.join(a.out_dir, "posts.json")
    existing = []
    if a.merge and os.path.exists(feed_path):
        existing = json.load(open(feed_path, encoding="utf-8")).get("posts", [])
    new_slugs = {p["slug"] for p in posts}
    keep = [p for p in existing if p["slug"] not in new_slugs]
    out_posts = [{k: p[k] for k in ("id", "slug", "title", "date", "category", "category_slug", "tags", "excerpt", "content", "image", "image_alt", "sources", "photo_query")} for p in posts] + keep
    out_posts.sort(key=lambda p: p["date"], reverse=True)
    feed = {"version": 1, "generated": dt.datetime.now(dt.timezone.utc).replace(microsecond=0).isoformat(), "posts": out_posts[:150]}
    json.dump(feed, open(feed_path, "w", encoding="utf-8"), ensure_ascii=False, indent=1)
    print(f"wrote {len(posts)} posts ({len(out_posts[:150])} in feed) to {feed_path}")


if __name__ == "__main__":
    sys.exit(main())
