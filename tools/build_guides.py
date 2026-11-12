#!/usr/bin/env python3
"""Turn content/guides/*.txt into WordPress block markup in content/<slug>.html.

Format of a guide file:
  eyebrow: / h1: / lead: / image: file | alt     header fields
  ## Heading                                      starts a section; next plain line = intro
  - Title | text                                  a numbered card in the section
  * text                                          a tick-list item
  faq: Heading   then   ? Question | Answer      FAQ block
  note: text                                      small print
  cta: Heading | text                             closing enquiry band
"""
import html, pathlib, re

ROOT = pathlib.Path(__file__).resolve().parent.parent
SRC = ROOT / "content" / "guides"
OUT = ROOT / "content"
IMG = "/wp-content/themes/letsby/assets/img/"


def esc(s: str) -> str:
    # Allow the odd intentional <a> link written in the source.
    parts = re.split(r"(<a [^>]+>.*?</a>)", s)
    return "".join(p if p.startswith("<a ") else html.escape(p, quote=False) for p in parts)


def block(inner: str) -> str:
    return f"<!-- wp:html -->\n{inner}\n<!-- /wp:html -->\n\n"


def build(path: pathlib.Path) -> str:
    head, sections, cur = {}, [], None
    faq, note, cta = None, None, None
    for raw in path.read_text().splitlines():
        line = raw.strip()
        if not line:
            continue
        m = re.match(r"^(eyebrow|h1|lead|image|note|cta|faq):\s*(.*)$", line)
        if m and cur is None or (m and m.group(1) in ("faq", "note", "cta")):
            key, val = m.groups()
            if key == "faq":
                faq = {"h": val, "items": []}
                cur = None
            elif key == "note":
                note = val
            elif key == "cta":
                cta = [x.strip() for x in val.split("|", 1)]
            else:
                head[key] = val
            continue
        if line.startswith("## "):
            cur = {"h": line[3:], "intro": [], "cards": [], "ticks": []}
            sections.append(cur)
            faq_active = False
        elif line.startswith("? ") and faq is not None:
            q, a = [x.strip() for x in line[2:].split("|", 1)]
            faq["items"].append((q, a))
        elif line.startswith("- ") and cur is not None:
            t, d = [x.strip() for x in line[2:].split("|", 1)]
            cur["cards"].append((t, d))
        elif line.startswith("* ") and cur is not None:
            cur["ticks"].append(line[2:])
        elif cur is not None:
            cur["intro"].append(line)

    out = []
    img = ""
    if "image" in head:
        f, alt = [x.strip() for x in head["image"].split("|", 1)]
        img = f'<img src="{IMG}{f}" alt="{html.escape(alt)}" width="1400" height="933">'
    hero_text = (
        f'<p class="la-eyebrow">{esc(head.get("eyebrow", "Landlord guide"))}</p>\n'
        f'<h1>{esc(head["h1"])}</h1>\n<p class="la-lead">{esc(head.get("lead", ""))}</p>\n'
        '<p class="la-meta">A Letsby Avenue landlord guide · <a href="/landlord-guides" style="color:#fff">All guides</a></p>'
    )
    inner = f'<div class="la-split"><div>{hero_text}</div>{img}</div>' if img else hero_text
    out.append(block(f'<section class="la-hero la-hero--page la-hero--plain">\n<div class="la-wrap">\n{inner}\n</div>\n</section>'))

    # Contents list
    toc = "".join(f'<a href="#s{i+1}">{i+1:02d} · {esc(s["h"])}</a>' for i, s in enumerate(sections))
    out.append(block(f'<section class="la-section la-section--tight"><div class="la-wrap la-wrap--narrow"><nav class="la-toc" aria-label="In this guide"><p class="la-eyebrow">In this guide</p>{toc}</nav></div></section>'))

    for i, s in enumerate(sections):
        bg = " la-section--paper" if i % 2 else ""
        intro = "".join(f'<p class="la-lead">{esc(p)}</p>' for p in s["intro"])
        cards = ""
        if s["cards"]:
            n = len(s["cards"])
            cols = "la-grid--2" if n in (2, 4) else "la-grid--3"
            cards = f'<div class="la-grid {cols}" style="margin-top:32px">' + "".join(
                f'<div class="la-card"><h3>{esc(t)}</h3><p>{esc(d)}</p></div>' for t, d in s["cards"]
            ) + "</div>"
        ticks = ""
        if s["ticks"]:
            ticks = '<ul class="la-ticks">' + "".join(f"<li>{esc(t)}</li>" for t in s["ticks"]) + "</ul>"
        out.append(block(
            f'<section class="la-section{bg}" id="s{i+1}"><div class="la-wrap">'
            f'<p class="la-num">{i+1:02d}</p><h2>{esc(s["h"])}</h2>{intro}{ticks}{cards}</div></section>'
        ))

    if faq:
        items = "".join(
            f"<details><summary>{esc(q)}</summary><div><p>{esc(a)}</p></div></details>" for q, a in faq["items"]
        )
        out.append(block(
            f'<section class="la-section"><div class="la-wrap la-wrap--narrow"><h2>{esc(faq["h"])}</h2>'
            f'<div class="la-faq">{items}</div></div></section>'
        ))
    if note:
        out.append(block(f'<section class="la-section--tight"><div class="la-wrap la-wrap--narrow"><p class="la-note">{esc(note)}</p></div></section>'))

    h, p = cta or ("Want some help with your property?", "Speak to our team about letting, management or switching agents.")
    slug = path.stem
    out.append(block(
        '<section class="la-section la-section--dark" id="enquiry"><div class="la-wrap"><div class="la-split">'
        f'<div><p class="la-eyebrow">Free rental valuation</p><h2>{esc(h)}</h2><p class="la-lead">{esc(p)}</p>'
        '<p>[la_phone] · [la_email]</p></div>'
        f'<div>[la_enquiry button="Get a free rental valuation" source="Guide: {slug}"]</div>'
        "</div></div></section>"
    ))
    return "".join(out)


if __name__ == "__main__":
    for f in sorted(SRC.glob("*.txt")):
        (OUT / f"{f.stem}.html").write_text(build(f))
        print("built", f.stem)
