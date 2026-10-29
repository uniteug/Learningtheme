// Accueil : volet portail (gratuit / freemium), programmes, carrousels, aucune offre payante mise en avant.
const { chromium } = require('playwright');
const base = process.env.LD_HOME || 'http://127.0.0.1:8080';
const assert = (ok, label) => { console.log((ok ? '✓ ' : '✗ ') + label); if (!ok) process.exitCode = 1; };

(async () => {
  const browser = await chromium.launch();
  const page = await browser.newPage({ viewport: { width: 1280, height: 900 } });
  const errors = [];
  page.on('pageerror', e => errors.push(e.message));
  await page.goto(base + '/');
  assert(await page.locator('h1').count() === 1, 'accueil : un seul H1');
  assert(await page.locator('body.ld-mode-editorial').count() === 1, 'volet portail par défaut (site freemium)');
  assert(await page.locator('#offres, .ld-plan--premium').count() === 0, 'portail : aucun tarif ni offre payante');
  assert(await page.locator('.ld-track').count() >= 2, 'programmes phares affichés');
  assert(await page.locator('.ld-mag__lead .wp-block-post-title').count() === 1, 'actualité à la une');
  const rail = page.locator('.ld-topics .wp-block-term-template');
  await page.waitForSelector('.ld-topics .ld-carousel-btn.is-next');
  const before = await rail.evaluate(el => el.scrollLeft);
  await page.locator('.ld-topics + .ld-carousel-nav .is-next, .ld-topics .ld-carousel-btn.is-next').first().click();
  await page.waitForTimeout(800);
  assert((await rail.evaluate(el => el.scrollLeft)) > before, 'carrousel des thèmes : bouton suivant');
  assert(await page.locator('.ld-country').count() >= 2, 'section Europe : pays');
  const order = await page.evaluate(() => [...document.querySelectorAll('.ld-country__name')].map(e => e.textContent));
  assert(order[0] === 'Italie', 'pays principal en premier');
  assert(errors.length === 0, 'aucune erreur JavaScript ' + errors.join(' | '));
  await browser.close();
})().catch(e => { console.error('FAIL', e.message); process.exit(1); });
