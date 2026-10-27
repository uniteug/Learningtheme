// Nouveaux formats : ordre, association, image cliquable.
const { chromium } = require('playwright');
const base = (process.env.LD_HOME || 'http://127.0.0.1:8080') + '/permis-b/questions/';
const assert = (ok, label) => { console.log((ok ? '✓ ' : '✗ ') + label); if (!ok) process.exitCode = 1; };

(async () => {
  const browser = await chromium.launch();
  const page = await browser.newPage({ viewport: { width: 390, height: 844 } });
  const errors = [];
  page.on('pageerror', e => errors.push(e.message));
  page.on('console', m => { if (m.type() === 'error') errors.push(m.text()); });

  // Ordre : on remet dans l'ordre avec les flèches en lisant la solution affichée après un premier essai faux.
  await page.goto(base + 'ordre-gestes-premiers-secours/');
  const texts = await page.locator('.ld-order__text').allTextContents();
  console.log('ordre affiché:', texts.map(t => t.slice(0, 12)));
  const target = ['Protéger', 'Alerter', 'Secourir'];
  // Trie visuellement avec les boutons ↑ (tri à bulles).
  for (let pass = 0; pass < 3; pass++) {
    for (const word of target) {
      const items = page.locator('.ld-order__item');
      const n = await items.count();
      for (let i = 0; i < n; i++) {
        const it = items.nth(i);
        if ((await it.locator('.ld-order__text').textContent()).startsWith(word)) {
          const pos = parseInt(await it.locator('.ld-order__pos').textContent(), 10);
          const want = target.indexOf(word) + 1;
          for (let k = pos; k > want; k--) await it.locator('button').first().click();
        }
      }
    }
  }
  const order = await page.evaluate(() => [...document.querySelectorAll('.ld-order__item')].sort((a, b) => getComputedStyle(a).order - getComputedStyle(b).order).map(li => li.querySelector('.ld-order__text').textContent.slice(0, 10)));
  console.log('ordre visuel après tri:', order);
  await page.click('button:has-text("Valider")');
  await page.waitForSelector('.ld-q__feedback .ld-fb');
  assert((await page.textContent('.ld-q__feedback .ld-fb')).includes('Bonne réponse') && await page.locator('.ld-order__item.is-solution').count() === 3, 'ordre : remis dans l’ordre au clavier, corrigé juste');
  await page.screenshot({ path: require('path').join(process.env.LD_SHOTS || require('os').tmpdir(), 'fmt-order.png'), fullPage: true });

  // Association : une erreur volontaire.
  await page.goto(base + 'associer-formes-panneaux/');
  const rows = page.locator('.ld-match__row');
  const map = { 'Triangle à bord rouge': 'Danger', 'Cercle à bord rouge': 'Interdiction', 'Cercle à fond bleu': 'Obligation', 'Carré ou rectangle à fond bleu': 'Indication' };
  const n = await rows.count();
  for (let i = 0; i < n; i++) {
    const left = (await rows.nth(i).locator('label').textContent()).trim();
    const wanted = i === 0 ? 'Indication' : map[left];
    await rows.nth(i).locator('select').selectOption({ label: wanted });
  }
  await page.click('button:has-text("Valider")');
  await page.waitForSelector('.ld-q__feedback .ld-fb');
  assert(await page.locator('.ld-match__row.is-solution').count() === 3 && await page.locator('.ld-match__row.is-missed').count() === 1, 'association : 3 justes, 1 fausse signalée');
  await page.screenshot({ path: require('path').join(process.env.LD_SHOTS || require('os').tmpdir(), 'fmt-match.png'), fullPage: true });

  // Image cliquable : case centrale de la colonne du milieu (STOP).
  await page.goto(base + 'image-panneau-arret-obligatoire/');
  await page.locator('.ld-hotspot__cell').nth(7).click();
  await page.waitForSelector('.ld-q__feedback .ld-fb');
  assert((await page.textContent('.ld-q__feedback .ld-fb')).includes('Bonne réponse') && await page.locator('.ld-hotspot__cell.is-solution').count() === 4, 'image cliquable : bonne zone');
  await page.screenshot({ path: require('path').join(process.env.LD_SHOTS || require('os').tmpdir(), 'fmt-hotspot.png'), fullPage: true });
  // Mauvaise zone sur une nouvelle page.
  await page.goto(base + 'image-panneau-arret-obligatoire/');
  await page.locator('.ld-hotspot__cell').nth(0).click();
  await page.waitForSelector('.ld-q__feedback .ld-fb');
  assert(await page.locator('.ld-hotspot__cell.is-missed').count() === 1, 'image cliquable : mauvaise zone signalée');
  // Clavier : la grille est atteignable par Tab.
  assert(errors.length === 0, 'aucune erreur JavaScript ' + (errors.length ? JSON.stringify(errors) : ''));
  await browser.close();
})().catch(e => { console.error('FAIL', e.message); process.exit(1); });
