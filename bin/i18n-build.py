#!/usr/bin/env python3
"""Compile une traduction (liste JSON msgid/context/plural/msgstr) en .po + .mo,
et les fichiers JSON des scripts (wp.i18n) pour les chaînes trouvées en JS.

Usage : python3 bin/i18n-build.py <traductions.json> <dossier languages> <domaine> <locale> [theme]
"""
import hashlib
import json
import struct
import sys
from pathlib import Path


def po_escape(s):
    return s.replace('\\', '\\\\').replace('"', '\\"').replace('\n', '\\n').replace('\t', '\\t')


def write_po(entries, path, domain, locale):
    lines = [
        'msgid ""', 'msgstr ""',
        f'"Project-Id-Version: {domain}\\n"',
        f'"Language: {locale}\\n"',
        '"MIME-Version: 1.0\\n"',
        '"Content-Type: text/plain; charset=UTF-8\\n"',
        '"Content-Transfer-Encoding: 8bit\\n"',
        '"Plural-Forms: nplurals=2; plural=(n != 1);\\n"',
        f'"X-Domain: {domain}\\n"', '',
    ]
    for e in entries:
        for ref in e.get('refs', []):
            lines.append(f'#: {ref}')
        if e.get('context'):
            lines.append(f'msgctxt "{po_escape(e["context"])}"')
        lines.append(f'msgid "{po_escape(e["msgid"])}"')
        if e.get('plural'):
            lines.append(f'msgid_plural "{po_escape(e["plural"])}"')
            plural = e.get('msgstr_plural') or e['msgstr']
            lines.append(f'msgstr[0] "{po_escape(e["msgstr"])}"')
            lines.append(f'msgstr[1] "{po_escape(plural)}"')
        else:
            lines.append(f'msgstr "{po_escape(e["msgstr"])}"')
        lines.append('')
    path.write_text('\n'.join(lines), encoding='utf-8')


def write_mo(entries, path, locale):
    header = ('Content-Type: text/plain; charset=UTF-8\nLanguage: ' + locale +
              '\nPlural-Forms: nplurals=2; plural=(n != 1);\n')
    pairs = {'': header}
    for e in entries:
        key = (e['context'] + '\x04' if e.get('context') else '') + e['msgid']
        val = e['msgstr']
        if e.get('plural'):
            key += '\x00' + e['plural']
            val += '\x00' + (e.get('msgstr_plural') or e['msgstr'])
        if e['msgstr']:
            pairs[key] = val
    keys = sorted(pairs)
    ids = b''.join(k.encode() + b'\x00' for k in keys)
    strs = b''.join(pairs[k].encode() + b'\x00' for k in keys)
    n = len(keys)
    koffset = 7 * 4
    voffset = koffset + n * 8
    start = voffset + n * 8
    offsets_k, offsets_v = [], []
    pos = 0
    for k in keys:
        b = k.encode()
        offsets_k.append((len(b), start + pos))
        pos += len(b) + 1
    spos = start + len(ids)
    pos = 0
    for k in keys:
        b = pairs[k].encode()
        offsets_v.append((len(b), spos + pos))
        pos += len(b) + 1
    out = struct.pack('Iiiiiii', 0x950412de, 0, n, koffset, voffset, 0, 0)
    for l, o in offsets_k:
        out += struct.pack('ii', l, o)
    for l, o in offsets_v:
        out += struct.pack('ii', l, o)
    path.write_bytes(out + ids + strs)


def write_js(entries, langdir, domain, locale, root):
    by_file = {}
    for e in entries:
        for ref in e.get('refs', []):
            f = ref.split(':')[0]
            if f.endswith('.js'):
                by_file.setdefault(f, []).append(e)
    for f, items in by_file.items():
        messages = {'': {'domain': 'messages', 'lang': locale, 'plural-forms': 'nplurals=2; plural=(n != 1);'}}
        for e in items:
            key = (e['context'] + '\x04' if e.get('context') else '') + e['msgid']
            messages[key] = [e['msgstr']] + ([e.get('msgstr_plural') or e['msgstr']] if e.get('plural') else [])
        md5 = hashlib.md5(f.encode()).hexdigest()
        data = {'translation-revision-date': '', 'generator': 'learn-drive i18n-build', 'domain': 'messages',
                'locale_data': {'messages': messages}}
        (langdir / f'{domain}-{locale}-{md5}.json').write_text(json.dumps(data, ensure_ascii=False), encoding='utf-8')


def main(src, langdir, domain, locale):
    entries = [e for e in json.loads(Path(src).read_text(encoding='utf-8')) if e.get('msgstr')]
    langdir = Path(langdir)
    langdir.mkdir(parents=True, exist_ok=True)
    # Un thème cherche « it_IT.mo » dans son dossier languages ; un plugin « domaine-it_IT.mo ».
    base = locale if (len(sys.argv) > 5 and sys.argv[5] == 'theme') else f'{domain}-{locale}'
    write_po(entries, langdir / f'{base}.po', domain, locale)
    write_mo(entries, langdir / f'{base}.mo', locale)
    write_js(entries, langdir, domain, locale, None)
    print(domain, locale, len(entries), 'chaînes')


if __name__ == '__main__':
    main(*sys.argv[1:5])
