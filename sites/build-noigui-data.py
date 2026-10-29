#!/usr/bin/env python3
"""Convertit le listato ministeriale « Patente AB » (extraction validée,
format questions.json) au format d'import Learn & Drive (JSON Lines).

Usage : python3 sites/build-noigui-data.py <questions.json> <dossier-figures> <sortie>
"""
import json
import re
import shutil
import sys
import unicodedata
from pathlib import Path


def slugify(text, max_words=9):
    text = unicodedata.normalize('NFKD', text).encode('ascii', 'ignore').decode()
    words = re.findall(r'[a-z0-9]+', text.lower())
    stop = {'il', 'lo', 'la', 'i', 'gli', 'le', 'un', 'una', 'di', 'del', 'della', 'e', 'a', 'in', 'che', 'si', 'per', 'con', 'su', 'da'}
    words = [w for w in words if w not in stop][:max_words]
    return '-'.join(words)


def main(src, figures, out):
    data = json.loads(Path(src).read_text(encoding='utf-8'))
    out = Path(out)
    (out / 'figures').mkdir(parents=True, exist_ok=True)

    topics = []
    for a in data['argomenti']:
        topics.append({
            'id': a['id'],
            'slug': slugify(a['breve'], 6),
            'name': a['breve'],
            'description': a['nome'].rstrip('…'),
            'quota': a['quota'],
        })
    slug_of = {t['id']: t['slug'] for t in topics}
    counts = {}
    for q in data['domande']:
        counts[q['argomento_id']] = counts.get(q['argomento_id'], 0) + 1
    for t in topics:
        t['count'] = counts.get(t['id'], 0)

    used = set()
    with (out / 'patente-ab.jsonl').open('w', encoding='utf-8') as fh:
        for q in data['domande']:
            figure = q.get('figura') or ''
            if figure:
                name = Path(figure).name
                used.add(name)
                figure = 'figures/' + name
            row = {
                'title': q['testo'].strip(),
                'slug': f"{slugify(q['testo'])}-{q['id']}",
                'format': 'tf',
                'answers': [
                    {'text': 'Vero', 'correct': bool(q['risposta'])},
                    {'text': 'Falso', 'correct': not q['risposta']},
                ],
                'tracks': ['patente-b', 'patente-a'],
                'topic': slug_of[q['argomento_id']],
                'country': 'italia',
                'official_id': str(q['id']),
                'group': str(q['quesito_id']),
                'source': f"Listato ministeriale MIT «Patente AB», quesito {q['quesito_id']}, affermazione {q['id']}",
                'access': 'free',
                'difficulty': 2,
            }
            if figure:
                row['image'] = figure
                row['image_alt'] = f"Figura del quesito n. {q['quesito_id']} ({q['argomento_nome']})"
            fh.write(json.dumps(row, ensure_ascii=False) + '\n')

    for name in sorted(used):
        shutil.copyfile(Path(figures) / name, out / 'figures' / name)

    meta = dict(data['meta'])
    meta['topics'] = topics
    meta['licenza'] = 'Testi del Ministero delle Infrastrutture e dei Trasporti, CC BY 3.0 IT'
    (out / 'meta.json').write_text(json.dumps(meta, ensure_ascii=False, indent=1), encoding='utf-8')
    print(len(data['domande']), 'affermazioni,', len(used), 'figure,', len(topics), 'argomenti')


if __name__ == '__main__':
    main(*sys.argv[1:4])
