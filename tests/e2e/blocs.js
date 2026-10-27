// Valide (comme l'éditeur) le balisage de tous les patterns, gabarits et parties.
const { chromium } = require('playwright');
const base = process.env.LD_HOME || 'http://127.0.0.1:8080';
(async () => {
  const browser = await chromium.launch();
  const page = await browser.newPage();
  await page.goto(base + '/wp-login.php');
  await page.fill('#user_login', 'admin'); await page.fill('#user_pass', 'admin-password-123');
  await page.click('#wp-submit'); await page.waitForURL(/wp-admin/, { waitUntil: 'domcontentloaded' });
  await page.goto(base + '/wp-admin/post-new.php');
  await page.waitForFunction(() => window.wp && wp.blocks && wp.blocks.getBlockTypes().length > 50, null, { timeout: 60000 });
  await page.waitForTimeout(2000);
  const report = await page.evaluate(async () => {
    const nonce = wpApiSettings.nonce;
    const get = (p) => fetch('/wp-json' + p, { headers: { 'X-WP-Nonce': nonce } }).then(r => r.json());
    const sources = [];
    for (const p of await get('/wp/v2/block-patterns/patterns')) if (p.name.startsWith('learn-drive/')) sources.push(['pattern ' + p.name, p.content]);
    for (const t of await get('/wp/v2/templates?context=edit&per_page=100')) if (t.theme === 'learn-drive') sources.push(['template ' + t.slug, t.content.raw]);
    for (const t of await get('/wp/v2/template-parts?context=edit&per_page=100')) if (t.theme === 'learn-drive') sources.push(['part ' + t.slug, t.content.raw]);
    for (const q of await get('/wp/v2/ld_lesson?context=edit&per_page=20')) sources.push(['lesson ' + q.slug, q.content.raw]);
    for (const q of await get('/wp/v2/posts?context=edit&per_page=20')) sources.push(['post ' + q.slug, q.content.raw]);
    for (const q of await get('/wp/v2/ld_guide?context=edit&per_page=20')) sources.push(['guide ' + q.slug, q.content.raw]);
    for (const q of await get('/wp/v2/ld_quiz?context=edit&per_page=20')) sources.push(['quiz ' + q.slug, q.content.raw]);
    for (const q of await get('/wp/v2/ld_question?context=edit&per_page=100')) sources.push(['question ' + q.slug, q.content.raw]);
    const out = [];
    const walk = (blocks, name) => blocks.forEach(b => {
      if (!b.isValid && b.name !== 'core/missing') out.push(name + ' → ' + b.name);
      if (b.name === 'core/missing') out.push(name + ' → bloc inconnu ' + b.attributes.originalName);
      walk(b.innerBlocks || [], name);
    });
    for (const [name, content] of sources) walk(wp.blocks.parse(content || ''), name);
    return { checked: sources.length, invalid: out };
  });
  console.log(`${report.checked} sources vérifiées`, report.invalid.length ? report.invalid : '');
  if (report.invalid.length || report.checked < 100) process.exitCode = 1;
  await browser.close();
})().catch(e => { console.error('FAIL', e.message); process.exit(1); });
