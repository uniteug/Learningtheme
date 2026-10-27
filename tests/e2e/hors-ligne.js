// Mode hors ligne : une page consultée reste lisible sans réseau, les autres
// mènent à la page « hors ligne » qui liste les pages disponibles.
const { chromium } = require('playwright');
const base = process.env.LD_HOME || 'http://127.0.0.1:8080';
const assert = (ok, label) => { console.log((ok ? '✓ ' : '✗ ') + label); if (!ok) process.exitCode = 1; };

(async () => {
  const browser = await chromium.launch();
  const context = await browser.newContext({ serviceWorkers: 'allow' });
  const page = await context.newPage();
  const errors = [];
  page.on('pageerror', e => errors.push(e.message));

  const manifest = await (await page.request.get(base + '/manifest.webmanifest')).json();
  assert(manifest.name && manifest.start_url && manifest.icons.length && manifest.display === 'standalone', 'manifeste valide');

  await page.goto(base + '/');
  assert(await page.locator('link[rel=manifest]').count() === 1, 'manifeste déclaré dans <head>');
  await page.evaluate(() => navigator.serviceWorker.ready);
  await page.reload(); // La page est désormais contrôlée par le service worker.
  assert(await page.evaluate(() => !!navigator.serviceWorker.controller), 'service worker actif');

  const question = base + '/permis-b/questions/priorite-a-droite-intersection-sans-signalisation/';
  await page.goto(question);
  const title = await page.locator('h1').first().textContent();
  await page.goto(base + '/mon-compte/');

  await context.setOffline(true);
  await page.goto(question);
  assert((await page.locator('h1').first().textContent()) === title, 'hors ligne : question déjà consultée lisible');
  assert(await page.locator('details, .ld-q').count() > 0, 'hors ligne : réponse et explication présentes');

  await page.goto(base + '/permis-b/questions/page-jamais-visitee/');
  assert((await page.textContent('h1')).includes('hors ligne'), 'hors ligne : page « hors ligne » pour une page non consultée');
  await page.waitForSelector('#ld-saved li a');
  const saved = await page.locator('#ld-saved li a').allTextContents();
  assert(saved.some(t => title.trim() && t.includes(title.trim().slice(0, 20))), 'hors ligne : la question consultée est proposée');
  assert(!saved.some(t => /Mon compte/i.test(t)), 'hors ligne : la page personnelle n’est pas gardée');

  await context.setOffline(false);
  assert(errors.length === 0, 'aucune erreur JavaScript ' + errors.join(' | '));
  await browser.close();
})().catch(e => { console.error('FAIL', e.message); process.exit(1); });
