#!/usr/bin/env python3
"""Build the Areas hub page and the 65 area pages (/areas/<slug>) from tools/areas.json.

Run from the repo root:  python3 tools/build_areas.py
Writes content/areas.html and content/areas/<slug>.html, and adds the matching entries to
content/pages.json (the Letsby setup tool in WordPress reads them, parent page first).
Each area is: [name, postcode, nearby slugs, intro, property types, getting around, who rents]
"""
import json, re, os

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
AREAS = json.load(open(os.path.join(ROOT, 'tools', 'areas.json'), encoding='utf-8'))

def nm(s):
    return ' '.join(w if w == 'in' else w[0].upper() + w[1:] for w in s.split('-'))

def slugify(name):
    return re.sub(r'^-|-$', '', re.sub(r'[^a-z0-9]+', '-', name.lower()))

# These pages went live first, with contractions in the standard sentences. They are kept exactly
# as published; the other areas use the full wording. (Both read fine; keep them as they are.)
CONTRACTED = {'Beeston', 'Belle Isle', 'Burley', 'Burmantofts', 'City Centre', 'Cottingley', 'Headingley', 'Holbeck',
              'Hunslet', 'Hyde Park', 'Kirkstall', 'Meanwood', 'Middleton', 'Richmond Hill', 'Weetwood', 'West Park', 'Woodhouse'}

def area_page(a):
    html = _area_page(a)
    if a[0] in CONTRACTED:
        html = (html.replace('we will confirm how', "we'll confirm how")
                    .replace('when you would like to let it. We will come back', "when you'd like to let it. We'll come back"))
    return html

def _area_page(a):
    name, pc, near, feel, housing, transport, tenants = a
    seen = []
    for s in near.split(','):
        if s not in seen: seen.append(s)
    links = ', '.join('<a href="/areas/%s">%s</a>' % (s, nm(s)) for s in seen)
    return (
        '<!-- wp:html -->\n<section class="la-hero la-hero--page la-hero--plain">\n  <div class="la-wrap">\n'
        '    <p class="la-eyebrow">Lettings in ' + name + '</p>\n'
        '    <h1>Letting agents and property management in ' + name + ', Leeds ' + pc + '.</h1>\n'
        '    <p class="la-lead">' + feel + '</p>\n'
        '    <p class="la-btns"><a class="la-btn la-btn--red" href="/contact#enquiry">Get a free rental valuation</a> <a class="la-btn la-btn--ghost" href="/landlords">See full management</a></p>\n'
        '  </div>\n</section>\n<!-- /wp:html -->\n\n'
        '<!-- wp:html -->\n<section class="la-section la-section--paper">\n  <div class="la-wrap">\n'
        '    <p class="la-eyebrow">Renting in ' + name + '</p>\n'
        '    <h2>What to know about letting a property in ' + name + '.</h2>\n'
        '    <div class="la-grid la-grid--3" style="margin-top:36px">\n'
        '      <div class="la-card"><h3>Property types</h3><p>' + housing + '</p></div>\n'
        '      <div class="la-card"><h3>Getting around</h3><p>' + transport + '</p></div>\n'
        '      <div class="la-card"><h3>Who rents here</h3><p>' + tenants + '</p></div>\n'
        '    </div>\n  </div>\n</section>\n<!-- /wp:html -->\n\n'
        '<!-- wp:html -->\n<section class="la-section">\n  <div class="la-wrap">\n'
        '    <p class="la-eyebrow">For landlords in ' + name + '</p>\n'
        '    <h2>Lettings and property management for your ' + name + ' property.</h2>\n'
        '    <p>If you own a property in ' + name + ', we can find and reference the tenant, or manage the tenancy day to day, including rent collection, maintenance and inspections. Our <a href="/lettings">lettings service</a> and <a href="/landlords">full management service</a> are both available, and the first step is a free rental valuation based on comparable homes nearby.</p>\n'
        '    <p>We also let and manage property in nearby ' + links + '. <a href="/areas">See all areas we cover</a>.</p>\n'
        '    <div class="la-faq" style="margin-top:28px">\n'
        '      <details><summary>Do you manage properties in ' + name + '?</summary><div><p>Tell us the address and we will confirm how we can help with lettings or full management at your ' + name + ' property. <a href="/contact#enquiry">Get in touch</a>.</p></div></details>\n'
        '      <details><summary>Who usually rents in ' + name + '?</summary><div><p>' + tenants + '</p></div></details>\n'
        '    </div>\n  </div>\n</section>\n<!-- /wp:html -->\n\n'
        '<!-- wp:html -->\n<section class="la-section la-section--dark" id="enquiry">\n  <div class="la-wrap">\n    <div class="la-split">\n      <div>\n'
        '        <h2>Letting a property in ' + name + '?</h2>\n'
        '        <p class="la-lead">Tell us the address and when you would like to let it. We will come back to you with a rental valuation and the next steps.</p>\n'
        '      </div>\n'
        '      <div>[la_enquiry button="Get a free rental valuation" source="Areas - ' + name + '"]</div>\n'
        '    </div>\n  </div>\n</section>\n<!-- /wp:html -->\n'
    )

GROUPS = [
 ('Leeds city centre and south Leeds', 'city-centre holbeck hunslet beeston cottingley middleton belle-isle richmond-hill burmantofts osmondthorpe'),
 ('Headingley, Hyde Park and the university area', 'headingley hyde-park woodhouse burley kirkstall meanwood weetwood west-park'),
 ('West Leeds', 'armley wortley farnley bramley rodley stanningley pudsey farsley calverley horsforth morley churwell gildersome'),
 ('North Leeds', 'adel cookridge bramhope alwoodley moortown chapel-allerton chapeltown roundhay oakwood gledhow harehills'),
 ('Aireborough, Wharfedale and Wetherby', 'guiseley yeadon rawdon otley wetherby boston-spa'),
 ('East and south-east Leeds', 'seacroft whinmoor cross-gates halton colton whitkirk temple-newsam barwick-in-elmet garforth kippax swillington rothwell oulton woodlesford methley scholes thorner'),
]

def hub_page():
    lists = ''.join(
        '<h3 style="margin-top:32px">' + t + '</h3><ul class="la-list" style="columns:2;margin-top:12px">'
        + ''.join('<li><a href="/areas/%s">%s</a></li>' % (s, nm(s)) for s in sl.split())
        + '</ul>' for t, sl in GROUPS)
    return (
        '<!-- wp:html -->\n<section class="la-hero la-hero--page la-hero--plain">\n  <div class="la-wrap">\n'
        '    <p class="la-eyebrow">Areas we cover</p>\n'
        '    <h1>Letting agents and property management across Leeds.</h1>\n'
        '    <p class="la-lead">Letsby Avenue lets and manages property across Leeds and the surrounding area, from the city centre and Headingley to Armley, Horsforth, Roundhay, Garforth and Wetherby. Choose your area to see what to expect when letting a property there.</p>\n'
        '    <p class="la-btns"><a class="la-btn la-btn--red" href="/contact#enquiry">Get a free rental valuation</a> <a class="la-btn la-btn--ghost" href="/landlords">See full management</a></p>\n'
        '  </div>\n</section>\n<!-- /wp:html -->\n\n'
        '<!-- wp:html -->\n<section class="la-section">\n  <div class="la-wrap">\n'
        '    <p class="la-eyebrow">Find your area</p>\n'
        '    <h2>Lettings and property management in %d parts of Leeds.</h2>\n' % len(AREAS) +
        '    <p>Whether you have a student house in Headingley, a family home in Pudsey or a flat in the city centre, we can find the tenant or manage the tenancy for you. Pick your area below, or <a href="/contact#enquiry">tell us your address</a> and we will come back to you.</p>\n'
        '    ' + lists + '\n  </div>\n</section>\n<!-- /wp:html -->\n\n'
        '<!-- wp:html -->\n<section class="la-section la-section--dark" id="enquiry">\n  <div class="la-wrap">\n    <div class="la-split">\n      <div>\n'
        '        <h2>Letting a property in Leeds?</h2>\n'
        '        <p class="la-lead">Tell us the address and when you would like to let it. We will come back to you with a rental valuation and the next steps.</p>\n'
        '      </div>\n'
        '      <div>[la_enquiry button="Get a free rental valuation" source="Areas hub"]</div>\n'
        '    </div>\n  </div>\n</section>\n<!-- /wp:html -->\n'
    )

def main():
    c = os.path.join(ROOT, 'content')
    os.makedirs(os.path.join(c, 'areas'), exist_ok=True)
    open(os.path.join(c, 'areas.html'), 'w', encoding='utf-8').write(hub_page())
    entries = [{"slug": "areas", "title": "Areas We Cover in Leeds",
                "seo": "Letting Agents & Property Management Across Leeds | Letsby Avenue",
                "desc": "Letsby Avenue lets and manages property across Leeds, from Headingley and Armley to Horsforth, Roundhay and Garforth. Find your area."}]
    for a in sorted(AREAS, key=lambda x: x[0]):
        name, pc = a[0], a[1]
        slug = slugify(name)
        open(os.path.join(c, 'areas', slug + '.html'), 'w', encoding='utf-8').write(area_page(a))
        entries.append({"slug": slug, "parent": "areas", "file": "areas/" + slug, "title": "Letting Agents in " + name,
                        "seo": "Letting Agents in %s, Leeds %s | Letsby Avenue" % (name, pc),
                        "desc": "Letting agents and property management in %s, Leeds %s. Rental valuations, tenant finding and full management from Letsby Avenue." % (name, pc)})
    pj = os.path.join(c, 'pages.json')
    pages = [p for p in json.load(open(pj, encoding='utf-8')) if p.get('slug') != 'areas' and p.get('parent') != 'areas']
    pages += entries
    open(pj, 'w', encoding='utf-8').write('[\n' + ',\n'.join('  ' + json.dumps(p, ensure_ascii=False) for p in pages) + '\n]\n')
    print('areas:', len(AREAS), 'pages.json entries:', len(pages))

if __name__ == '__main__':
    main()
