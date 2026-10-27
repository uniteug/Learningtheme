// Assistant de démarrage : écran complet, envoi, récapitulatif (réglages actuels conservés).
const { chromium } = require('playwright');
const base = process.env.LD_HOME || 'http://127.0.0.1:8080';
const assert = (ok, label) => { console.log((ok ? '✓ ' : '✗ ') + label); if (!ok) process.exitCode = 1; };

(async () => {
  const browser = await chromium.launch();
  const page = await browser.newPage({ viewport: { width: 1280, height: 900 } });
  const errors = [];
  page.on('pageerror', e => errors.push(e.message));
  await page.goto(base + '/wp-login.php');
  await page.fill('#user_login', 'admin'); await page.fill('#user_pass', 'admin-password-123');
  await Promise.all([page.waitForNavigation({ waitUntil: 'domcontentloaded' }), page.click('#wp-submit')]);
  await page.goto(base + '/wp-admin/admin.php?page=learn-drive-setup', { waitUntil: 'domcontentloaded' });
  assert(await page.locator('input[name=sector]').count() === 4, 'assistant : 4 secteurs');
  assert(await page.locator('input[name=variation]').count() >= 5, 'assistant : variations de style avec aperçu');
  assert(await page.locator('input[name=sector][value=driving]').isChecked(), 'assistant : secteur actuel présélectionné');
  assert(!(await page.locator('input[name=demo]').isChecked()), 'assistant : démo décochée quand du contenu existe');
  await page.screenshot({ path: require('path').join(process.env.LD_SHOTS || require('os').tmpdir(), 'assistant.png'), fullPage: true });
  await Promise.all([page.waitForNavigation({ waitUntil: 'domcontentloaded' }), page.click('#submit')]);
  assert((await page.textContent('h1')).includes('prêt'), 'assistant : récapitulatif affiché');
  assert(await page.locator('.ld-setup__report li').count() >= 4, 'assistant : actions listées');
  const home = await page.request.get(base + '/');
  assert(home.ok(), 'site toujours en ligne après l’assistant');
  assert(errors.length === 0, 'aucune erreur JavaScript ' + errors.join(' | '));
  await browser.close();
})().catch(e => { console.error('FAIL', e.message); process.exit(1); });
