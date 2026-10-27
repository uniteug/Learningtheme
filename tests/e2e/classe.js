// Espace auto-école : le moniteur crée une classe et assigne un quiz, l'élève
// la rejoint avec le code, le moniteur voit l'élève et exporte le suivi.
const { chromium } = require('playwright');
const base = process.env.LD_HOME || 'http://127.0.0.1:8080';
const assert = (ok, label) => { console.log((ok ? '✓ ' : '✗ ') + label); if (!ok) process.exitCode = 1; };

/** Attend que l'espace « Mon compte » soit interactif (Interactivity API hydratée). */
const ready = (page) => page.waitForSelector('.ld-dashboard.is-ready');

async function login(page, user, pass) {
  await page.goto(base + '/wp-login.php', { waitUntil: 'domcontentloaded' });
  await page.fill('#user_login', user); await page.fill('#user_pass', pass);
  await Promise.all([page.waitForNavigation({ waitUntil: 'domcontentloaded' }), page.click('#wp-submit')]);
}

(async () => {
  const browser = await chromium.launch();
  const admin = await (await browser.newContext()).newPage();
  admin.on('dialog', d => d.accept());
  const errors = [];

  // 1. Le moniteur (ici l'administrateur) crée une classe et assigne un quiz.
  await login(admin, 'admin', 'admin-password-123');
  await admin.goto(base + '/wp-admin/admin.php?page=learn-drive-classes', { waitUntil: 'domcontentloaded' });
  const name = 'Classe e2e ' + Date.now();
  await admin.fill('#ld-class-name', name);
  await Promise.all([admin.waitForNavigation({ waitUntil: 'domcontentloaded' }), admin.click('input[type=submit][value="Créer la classe"]')]);
  const code = (await admin.textContent('.ld-class-code--big code')).trim();
  assert(/^[A-Z2-9]{4}-[A-Z2-9]{4}$/.test(code), 'moniteur : classe créée, code ' + code);
  const quizTitle = (await admin.locator('#ld-class-quiz option').first().textContent()).trim();
  await Promise.all([admin.waitForNavigation({ waitUntil: 'domcontentloaded' }), admin.click('input[type=submit][value="Assigner"]')]);
  assert((await admin.textContent('.ld-class-assignments')).includes(quizTitle), 'moniteur : devoir assigné');
  const classUrl = admin.url();

  // 2. Compte élève (API REST, nonce de l'administrateur).
  const nonce = await admin.evaluate(() => fetch('/wp-admin/admin-ajax.php?action=rest-nonce').then(r => r.text()));
  const login_ = 'eleve' + Date.now();
  const student = await admin.evaluate(async ([nonce, login_]) => (await fetch('/wp-json/wp/v2/users', {
    method: 'POST', headers: { 'X-WP-Nonce': nonce, 'Content-Type': 'application/json' },
    body: JSON.stringify({ username: login_, email: login_ + '@example.com', password: 'eleve-password-123', name: 'Élève Test', roles: ['subscriber'] }),
  })).json(), [nonce, login_]);
  assert(student.id > 0, 'compte élève créé');

  // 3. L'élève rejoint la classe depuis « Mon compte ».
  const page = await (await browser.newContext({ viewport: { width: 390, height: 844 } })).newPage();
  page.on('dialog', d => d.accept());
  page.on('pageerror', e => errors.push(e.message));
  let expected = false; // Erreur attendue (code inconnu → 404 de l'API) : ne compte pas.
  page.on('console', m => { if (!expected && m.type() === 'error' && !m.text().includes('TUNNEL')) errors.push(m.text()); });
  await login(page, login_, 'eleve-password-123');
  await page.goto(base + '/mon-compte/', { waitUntil: 'domcontentloaded' });
  await ready(page);
  await page.fill('#ld-class-code', code.toLowerCase().replace('-', ''));
  await Promise.all([page.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 15000 }), page.click('button:has-text("Rejoindre la classe")')]);
  const card = page.locator('.ld-class').filter({ hasText: name });
  assert(await card.count() === 1, 'élève : classe rejointe avec le code saisi en minuscules');
  assert((await card.locator('.ld-class__todo li.is-todo').textContent()).includes(quizTitle), 'élève : devoir « à faire » affiché');
  await page.screenshot({ path: require('path').join(process.env.LD_SHOTS || require('os').tmpdir(), 'classe-eleve.png'), fullPage: true });

  // Mauvais code : message d'erreur, pas de rechargement.
  expected = true;
  await ready(page);
  await page.fill('#ld-class-code', 'ZZZZ-ZZZZ');
  await page.click('button:has-text("Rejoindre la classe")');
  await page.waitForFunction(() => document.querySelector('.ld-dashboard__message').textContent.includes('inconnu'), null, { timeout: 10000 }).catch(async (e) => { console.log('message:', await page.textContent('.ld-dashboard__message'), page.url()); throw e; });
  expected = false;
  assert(true, 'élève : code inconnu signalé');

  // 4. Le moniteur voit l'élève et exporte le suivi.
  await admin.goto(classUrl, { waitUntil: 'domcontentloaded' });
  assert((await admin.textContent('.ld-class-report')).includes('Élève Test'), 'moniteur : élève visible dans le suivi');
  const exportUrl = await admin.getAttribute('a:has-text("Exporter en CSV")', 'href');
  const csv = await admin.request.get(exportUrl);
  const body = await csv.text();
  assert(csv.headers()['content-type'].startsWith('text/csv') && body.includes('Élève Test') && body.includes(quizTitle), 'moniteur : export CSV');
  const forged = await admin.request.get(exportUrl.replace(/_wpnonce=[^&]+/, '_wpnonce=bad'));
  assert(forged.status() === 403, 'export refusé sans nonce valide');

  // 5. L'élève quitte la classe.
  await page.goto(base + '/mon-compte/', { waitUntil: 'domcontentloaded' });
  await ready(page);
  await Promise.all([page.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 15000 }), page.locator('.ld-class').filter({ hasText: name }).locator('button:has-text("Quitter la classe")').click()]);
  assert(await page.locator('.ld-class').filter({ hasText: name }).count() === 0, 'élève : classe quittée');
  assert(errors.length === 0, 'aucune erreur JavaScript ' + errors.join(' | '));

  // Nettoyage.
  await admin.goto(classUrl, { waitUntil: 'domcontentloaded' });
  await Promise.all([admin.waitForNavigation({ waitUntil: 'domcontentloaded' }), admin.click('a:has-text("Supprimer la classe")')]);
  assert(!(await admin.textContent('.ld-classes')).includes(name), 'moniteur : classe supprimée');
  await admin.evaluate(([nonce, id]) => fetch(`/wp-json/wp/v2/users/${id}?force=true&reassign=1`, { method: 'DELETE', headers: { 'X-WP-Nonce': nonce } }), [nonce, student.id]);
  await browser.close();
})().catch(e => { console.error('FAIL', e.message); process.exit(1); });
