// Parcours réels : question isolée, quiz thématique, examen blanc.
const { chromium } = require('playwright');
const base = process.env.LD_HOME || 'http://127.0.0.1:8080';
const log = (...a) => console.log(...a);
const assert = (ok, label) => { console.log((ok ? '✓ ' : '✗ ') + label); if (!ok) process.exitCode = 1; };
/** Répond à la question courante, quel que soit son format. */
async function answer(current, validate) {
  const ctx = await current.getAttribute('data-wp-context');
  const format = JSON.parse(ctx).format;
  if (format === 'numeric') await current.locator('input[type=number]').fill('10');
  else if (format === 'match') { const selects = current.locator('select'); for (let i = 0; i < await selects.count(); i++) await selects.nth(i).selectOption({ index: 1 }); }
  else if (format === 'hotspot') { await current.locator('.ld-hotspot__cell').first().click(); return; }
  else if (format !== 'order') await current.locator('.ld-opt').first().click();
  if (validate && ['multiple', 'numeric', 'order', 'match'].includes(format)) await current.locator('button:has-text("Valider")').click();
}

(async () => {
  const browser = await chromium.launch();
  const page = await browser.newPage({ viewport: { width: 390, height: 844 } });
  const errors = [];
  page.on('pageerror', e => errors.push('pageerror: ' + e.message));
  page.on('console', m => { if (m.type() === 'error' && !m.text().includes('TUNNEL')) errors.push(m.text()); });

  // 1. Page question : choix → correction serveur.
  await page.goto(base + '/permis-b/questions/priorite-a-droite-intersection-sans-signalisation/');
  await page.locator('.ld-opt').nth(1).click();
  await page.waitForSelector('.ld-q__feedback .ld-fb');
  assert((await page.textContent('.ld-q__feedback .ld-fb')).includes('Mauvaise réponse'), 'question : mauvaise réponse corrigée par le serveur');
  assert((await page.locator('.ld-opt').nth(0).getAttribute('class')).includes('is-solution'), 'question : bonne réponse marquée');
  log('Q1 missed class on B:', await page.locator('.ld-opt').nth(1).getAttribute('class'));
  assert(await page.locator('.ld-opt input').first().isDisabled(), 'question : propositions verrouillées après correction');
  log('details answer in HTML:', (await page.textContent('.ld-answer__solution')).trim());
  await page.screenshot({ path: require('path').join(process.env.LD_SHOTS || require('os').tmpdir(), 'e2e-question.png'), fullPage: true });

  // 2. Quiz thématique : démarrer, répondre, suivant, résultat.
  await page.goto(base + '/permis-b/quiz/');
  const links = await page.locator('.wp-block-post-title a').evaluateAll(as => as.map(a => a.href)); const quizUrl = links.find(h => !h.includes('examen-blanc'));
  log('quiz url', quizUrl);
  await page.goto(quizUrl);
  await page.click('.ld-quiz__start button');
  assert(await page.locator('.ld-quiz.is-playing').count() === 1, 'quiz : mode plein écran');
  const total = await page.locator('.ld-q').count();
  for (let i = 0; i < total; i++) {
    const current = page.locator('.ld-q.is-current');
    await answer(current, true);
    await current.locator('.ld-q__feedback .ld-fb').waitFor();
    if (i === 0) await page.screenshot({ path: require('path').join(process.env.LD_SHOTS || require('os').tmpdir(), 'e2e-quiz-feedback.png') });
    if (i < total - 1) await page.click('.ld-quiz__nav button:has-text("Suivante")');
  }
  await page.click('.ld-quiz__nav button:has-text("Voir mon résultat")');
  await page.waitForSelector('.ld-quiz__result:not([hidden])');
  log('thematic result:', (await page.textContent('.ld-quiz__score')).trim(), '|', (await page.textContent('.ld-quiz__verdict')).trim());
  assert(await page.locator('.ld-quiz__topics li').count() > 0, 'quiz : erreurs par thème');
  await page.screenshot({ path: require('path').join(process.env.LD_SHOTS || require('os').tmpdir(), 'e2e-quiz-result.png'), fullPage: false });

  // 3. Examen blanc : session aléatoire, chrono, correction à la fin.
  await page.goto(base + '/permis-b/examen-blanc/');
  await page.click('.ld-quiz__start a');
  await page.waitForURL(/ld_session=/);
  assert(page.url().includes('ld_session='), 'examen : session aléatoire créée');
  const robots = await page.getAttribute('meta[name=robots]', 'content');
  const canonical = await page.getAttribute('link[rel=canonical]', 'href');
  assert(robots.includes('noindex') && canonical.endsWith('/permis-b/examen-blanc/'), 'examen : session noindex + canonique');
  await page.click('.ld-quiz__start button:has-text("Commencer")');
  log('timer:', (await page.textContent('.ld-quiz__timer')).trim());
  assert(await page.locator('.ld-quiz__nav button:has-text("Précédente")').isHidden(), 'examen : retour arrière interdit (France)');
  const examTotal = await page.locator('.ld-q').count();
  for (let i = 0; i < examTotal; i++) {
    const current = page.locator('.ld-q.is-current');
    await answer(current, false);
    // aucune correction pendant l'examen
    if (i === 0) assert((await current.locator('.ld-q__feedback').innerHTML()) === '', 'examen : aucun corrigé pendant l’épreuve');
    if (i < examTotal - 1) await page.click('.ld-quiz__nav button:has-text("Suivante")');
  }
  await page.click('.ld-quiz__nav button:has-text("Terminer")');
  await page.waitForSelector('.ld-quiz__result:not([hidden])');
  log('exam result:', (await page.textContent('.ld-quiz__score')).trim(), '|', (await page.textContent('.ld-quiz__verdict')).trim(), '|', (await page.textContent('.ld-quiz__threshold')).trim());
  await page.screenshot({ path: require('path').join(process.env.LD_SHOTS || require('os').tmpdir(), 'e2e-exam-result.png'), fullPage: false });
  await page.click('button:has-text("Revoir la correction")');
  assert(await page.locator('.ld-q.is-current .ld-q__feedback .ld-fb').count() === 1, 'examen : correction consultable');
  await page.screenshot({ path: require('path').join(process.env.LD_SHOTS || require('os').tmpdir(), 'e2e-exam-review.png'), fullPage: false });

  assert(errors.length === 0, 'aucune erreur JavaScript ' + (errors.length ? JSON.stringify(errors) : ''));
  await browser.close();
})().catch(e => { console.error('E2E FAIL', e.message); process.exit(1); });
