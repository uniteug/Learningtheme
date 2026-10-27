# Learn & Drive — thème WordPress d'apprentissage + plugin compagnon

[![CI](https://github.com/uniteug/Learningtheme/actions/workflows/ci.yml/badge.svg?branch=claude/gifted-faraday-xppqru)](https://github.com/uniteug/Learningtheme/actions/workflows/ci.yml)

Thème bloc (Full Site Editing) pour les sites de formation et d'exercices — permis de conduire d'abord, toute matière ensuite — avec rubriques **Actualités** et **Infos & démarches**, et un SEO intégré au produit.

| Dossier | Rôle |
|---|---|
| [`learn-drive/`](learn-drive/) | **Thème bloc** : affichage uniquement (theme.json v3, gabarits, parties, patterns, 5 variations de style, mode sombre). Fonctionne seul. |
| [`learn-drive-core/`](learn-drive-core/) | **Plugin compagnon** : contenus d'apprentissage, moteur de quiz, accès gratuit / premium, API REST, SEO (JSON-LD, sitemaps, maillage), import / export, démo. Si l'on change de thème, on garde quiz, comptes et URL. |
| [`tests/`](tests/) | Tests d'intégration PHP (87 vérifications), tests navigateur Playwright (`tests/e2e/`), installation de test (`tests/bin/`). |
| [`bin/`](bin/) | Minification CSS / JS. |

Architecture conforme au cahier des charges : *le thème affiche, le plugin porte les données* (règle d'or des thèmes du répertoire WordPress.org : pas de types de contenus dans le thème).

---

## Tester en ligne (sans installation)

[**Ouvrir la démo dans WordPress Playground**](https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/uniteug/Learningtheme/claude/gifted-faraday-xppqru/blueprint.json) — WordPress complet dans le navigateur : le thème, le plugin et la démo s'installent automatiquement (compter 1 à 2 minutes au premier chargement). Connecté en administrateur ; rien n'est conservé à la fermeture de l'onglet.

## Installation

1. Copier `learn-drive/` dans `wp-content/themes/` et `learn-drive-core/` dans `wp-content/plugins/` (ou téléverser les zips).
2. Activer le thème puis le plugin (WordPress ≥ 7.0, PHP ≥ 8.1).
3. **Réglages › Permaliens** : structure `/actualites/%postname%/`, base des catégories `rubrique`.
4. **Learn & Drive › Import, export, démo › Importer la démo** — ou `wp learn demo`.
5. **Apparence › Éditeur › Styles** : choisir l'ambiance (Route claire, Coach, Magazine, Neutre, Route de nuit), les couleurs et les polices.

Contenu de démonstration : 2 pays (France QCM, Italie vrai/faux) avec leurs règles d'examen, 5 catégories, 14 thèmes officiels, **125 questions** (dont ordre, association et image cliquable) expliquées (≥ 45 mots, source citée), 14 quiz dont 2 examens blancs, 2 cours, 8 leçons, 14 panneaux dessinés localement, 15 définitions, 10 actualités, 10 guides et les pages éditoriales. Les actualités de démo sont des sujets « evergreen » (aucune réforme ni chiffre inventé) ; les prix des guides sont indicatifs et à vérifier.

## Ce que fait le produit

### Thème (`learn-drive`)
- **Design** : 12 jetons de couleur nommés par rôle (fond, surface, texte, texte discret, bordure, primaire, secondaire, accent, réussite, erreur, avertissement, premium), typographie fluide, rayons / ombres / zones tactiles en jetons. Changer la palette dans l'éditeur restyle tout, quiz compris.
- **5 variations** : Route claire (défaut, Atkinson Hyperlegible), Coach (violet, Plus Jakarta Sans + Inter, boutons pilules), Magazine (Source Serif 4 + Inter, filets), Neutre (polices système), Route de nuit.
- **Mode sombre** automatique (préférence système) + bascule manuelle mémorisée, palette sombre propre à chaque variation, sans flash au chargement.
- **Gabarits** : accueil, page pilier par catégorie (`/permis-b/`), thème officiel, cours, leçon (sommaire collant), question (2 mises en page), quiz / examen blanc, fiche panneau et index filtrable par famille, actualité, rubrique, info & démarche, lexique, auteur, recherche, 404, pages (standard, pleine largeur, sans distraction).
- **Parties** : 3 en-têtes, 3 pieds de page, signature d'article, encadré auteur, carte de contenu.
- **Patterns** (accueil dans l'ordre du cahier des charges, direction A) : bandeau H1 + chiffres de preuve calculés en direct, choix de la catégorie, quiz par thème, question du jour, actualités (1 à la une + 4), infos & démarches, théorie / pratique, témoignages, comparatif gratuit / premium, application, FAQ éditoriale ; plus « Ce qui change pour vous », « En résumé », étapes, tableau de coûts, politique éditoriale, « Testez-vous », appel premium, publicité à hauteur réservée.
- **Performance** : aucune requête tierce (polices WOFF2 locales, sous-ensembles latin / latin étendu, `font-display: swap`, préchargement de la police des titres), CSS par bloc chargé seulement si le bloc est présent et inséré en ligne, Speculation Rules (préchargement au survol), images 16:9 réservées (CLS nul).

### Plugin (`learn-drive-core`)
- **Contenus** : cours, leçons, questions, quiz, fiches, infos & démarches, lexique ; actualités = articles avec rubriques. Taxonomies : pays, catégorie, thème officiel, type d'épreuve, niveau, matière, famille de fiches — **libellés renommables** (« Catégorie de permis » → « Classe », « Niveau scolaire »…).
- **URL en silos** : `/permis-b/`, `/permis-b/cours/{leçon}/`, `/permis-b/formation/{cours}/`, `/permis-b/quiz/{thème}/`, `/permis-b/examen-blanc/` (une seule URL), `/permis-b/questions/{slug}/`, `/panneaux/{slug}/`, `/infos/{slug}/`, `/lexique/{slug}/`, `/actualites/{slug}/`. Slugs traduisibles, 301 automatique vers l'URL canonique.
- **Moteur de quiz** (Interactivity API, aucun React / jQuery côté visiteur, ~9 Ko minifié) :
  - formats vrai/faux, choix unique, choix multiple (juste seulement si toutes les bonnes cases), numérique avec tolérance et unité, **remettre dans l'ordre** (flèches, accessible au clavier), **association** (listes déroulantes), **image cliquable** (grille de zones-boutons posée sur l'image, zones dessinées à la souris dans l'admin), carte mémoire — l'ordre juste et les correspondances ne figurent jamais dans le code source ; un examen blanc ne tire que les formats de l'épreuve officielle du pays ;
  - modes entraînement / thématique (correction immédiate + explication + lien vers la leçon) et **examen blanc** (tirage aléatoire réparti par thème, jeton de session signé, chrono total ou par question, retour arrière paramétrable, seuil, points négatifs, questions éliminatoires) ;
  - **règles par pays et par catégorie** en métadonnées : France 40 questions / 20 s / 35 bonnes réponses / pas de retour ; Italie 30 vrai-faux / 20 min / 3 erreurs — ajouter un pays ne demande aucun code ;
  - une question par écran, plein écran sans distraction, grandes zones tactiles, reprise automatique d'un quiz interrompu (sauvegarde dans le navigateur), temps majoré, texte agrandi, police dys, signalement d'erreur, défi par lien, résultat détaillé avec erreurs par thème ;
  - **correction toujours côté serveur** : aucune bonne réponse d'examen ou premium n'est envoyée au navigateur.
- **Accès** : tout gratuit / tout payant / mixte, héritage site › catégorie › cours › leçon › contenu, règles gratuit / inscription / premium, début de leçon gratuit (bloc « Lire la suite »), vitrine des énoncés pour Google. Table d'accès indépendante du paiement : **WooCommerce** (produit → offre, remboursement → fermeture), **codes d'accès** générés par lot (auto-écoles), ouverture manuelle. Écran récapitulatif en arbre.
- **Comptes** : tableau de bord (taux de réussite aux examens blancs, prédiction « prêt pour l'examen », série de jours, erreurs à revoir, progression par thème, historique), objectif et date d'examen, **révision espacée** J+1 / J+3 / J+7, export et suppression en un clic, exportateur / effaceur RGPD de WordPress.
- **Espace auto-école (B2B)** : rôle **Moniteur** (accès limité à son espace), classes avec **code d'invitation** (ABCD-EFGH, places limitées, inscriptions ouvertes / fermées, code renouvelable), l'élève rejoint la classe depuis « Mon compte » et peut la quitter à tout moment ; **devoirs** (quiz ou examen blanc, date limite, « à faire / fait / en retard ») ; **suivi** par élève (dernière activité, questions répondues, taux de réussite, examens blancs, prêt pour l'examen, thème le plus faible, erreurs à revoir, score par devoir) ; **export CSV** (Excel, formules neutralisées) ; chaque moniteur ne voit que ses classes ; exportateur / effaceur RGPD.
- **SEO intégré** (cède les balises à Yoast / Rank Math / SEOPress / AIOSEO et **fusionne** son JSON-LD dans leur graphe) :
  - titres et descriptions par modèles (`{title} – Quiz {track} {country} | {site}`), un seul H1, canonique auto-référente, pagination en vraies URL ;
  - `noindex` : recherche, 404, paramètres de session d'examen (canonique vers l'examen), comptes, questions dont l'explication fait moins de 40 mots (alerte dans la santé SEO), quasi-doublons avec choix d'une canonique ;
  - **chaque question indexable** a sa page avec énoncé, propositions, réponse et explication **dans le HTML dès le chargement** (repliées derrière « Voir la réponse ») ;
  - JSON-LD (un seul graphe) : WebSite, EducationalOrganization, WebPage / CollectionPage / ProfilePage, BreadcrumbList, NewsArticle (images 16:9 / 4:3), Article, LearningResource, Course + ItemList, Quiz + Question + Answer + educationalAlignment, cartes mémoire (Flashcard), DefinedTerm, Person, VideoObject, contenu payant (`isAccessibleForFree` + `hasPart.cssSelector`). FAQPage / Practice Problem désactivés (résultats enrichis retirés par Google) ;
  - Open Graph / X complets, **images de partage générées** (titre + catégorie + couleur), `max-image-preview:large` ;
  - sitemaps natifs (2 000 URL par fichier, `lastmod` fiable, noindex exclus), **sitemap Google News 48 h**, IndexNow, `/llms.txt`, blocage des robots d'IA par agent ;
  - maillage automatique : pages piliers, leçon / quiz / fiche / questions proches sur chaque question, actualités liées sur chaque leçon, « Testez-vous » et « À réviser » sur chaque actualité, liens du lexique (1re occurrence), ancres de titres et sommaire ;
  - écran **Santé SEO** : questions trop minces, images sans texte alternatif, titres trop longs, doublons, pages orphelines, signalements, journal des 404 et gestionnaire de redirections 301.
- **Médias** : AVIF / WebP (original conservé), qualité 75, tailles utiles uniquement (400 / 800 / 1200 / 1600 + 1200×675), noms de fichiers lisibles, façade vidéo YouTube / Vimeo (rien chargé avant le clic), CDN d'images, avatars locaux (aucune requête Gravatar).
- **API REST** `/wp-json/learn/v1/` : `check`, `quiz/{id}`, `quiz/{id}/session`, `quiz/{id}/submit`, `questions`, `questions/{id}`, `report`, `me/progress`, `me/goal`, `me/access`, `me/classes`, `access/redeem`, `classes/join`, `classes/{id}/membership` ; clés d'API (`X-Learn-Key`) pour l'application, limitation de débit, pagination bornée anti-aspiration ; webhooks signés HMAC (inscription, accès ouvert, quiz terminé).
- **Import / export** CSV (`,` ou `;`), JSON, JSON Lines en continu ; WP-CLI : `wp learn import fichier.csv [--dry-run]`, `wp learn export fichier.jsonl`, `wp learn demo`, `wp learn og`.

## Tests réalisés en local

Environnement : WordPress 7.1.3, PHP 8.3, SQLite, serveur PHP intégré, Chromium (Playwright), Lighthouse 12.

| Vérification | Résultat |
|---|---|
| `php -l` sur tous les fichiers | OK |
| PHPCS — WordPress Coding Standards 3 + PHPCompatibilityWP (`phpcs.xml.dist`) | **0 erreur** (8 avertissements volontaires : requêtes à `posts_per_page` élevé pour les sitemaps / imports) |
| Tests d'intégration `php tests/integration.php <wp>` | **87 / 87** (espace auto-école : rôles, codes, places, devoirs, suivi, CSV, API, RGPD ; correction des 8 formats dont ordre, association et image cliquable, règles FR / IT, tirage reproductible et réparti, sessions signées, seuils 35/40 et 3 erreurs, accès / codes / héritage / révocation, révision espacée, noindex des questions minces, explication obligatoire, modèles de titre, URL en silo, import CSV `;` + réimport sans doublon, export) |
| Parcours navigateur (mobile 390 px) | question isolée (correction serveur, solution marquée par icône + texte), quiz thématique complet, examen blanc (session `noindex` + canonique, chrono 20 s, aucun corrigé pendant l'épreuve, résultat avec seuil « 35 bonnes réponses sur 40 », erreurs par thème, revue de la correction) ; formats ordre (clavier), association et image cliquable ; auto-école de bout en bout (le moniteur crée la classe et assigne un quiz, l'élève la rejoint avec le code, le moniteur voit l'élève et exporte le CSV, l'élève quitte la classe) — **aucune erreur JavaScript** |
| Validation des blocs (comme l'éditeur) | **210 / 210** patterns, gabarits, parties et contenus valides |
| Back-office | réglages, santé SEO, accès et codes, outils, éditeur de questions, éditeur de site : aucune erreur |
| Question premium | ni la solution ni l'explication dans le HTML, l'API ou le JSON-LD ; `isAccessibleForFree: false` |
| Lighthouse mobile (6 gabarits clés, HTML compressé) | Performance **97–100**, Accessibilité **100**, Bonnes pratiques **100**, SEO **100** ; LCP 1,7–2,3 s, CLS ≤ 0,036, TBT 0 ms |

Reproduire (les mêmes étapes tournent dans GitHub Actions à chaque push, `.github/workflows/ci.yml`) :

```sh
composer install && vendor/bin/phpcs                       # normes WordPress
bash tests/bin/setup-wordpress.sh /tmp/wp                  # WordPress 7.1.3 + SQLite + démo
php tests/integration.php /tmp/wp                          # tests d'intégration
(cd /tmp/wp && php -S 127.0.0.1:8080 router.php &)         # serveur de test
cd tests/e2e && npm install && npx playwright install chromium && npm test
```

Compte de test : `admin` / `admin-password-123`.

## Choix et écarts par rapport au cahier des charges

- **Bloc « Fil d'Ariane »** : WordPress 7 fournit `core/breadcrumbs` ; le plugin le pilote (silos) et en dérive le `BreadcrumbList`, l'affichage et le balisage ne peuvent pas diverger.
- **Grilles de catégories / thèmes** : blocs natifs `core/terms-query` (WordPress 6.9+), le thème reste utilisable sans plugin. Réglage « Pays principal » pour n'afficher que les catégories du pays sur l'accueil.
- **Chiffres de preuve** du bandeau : calculés en direct (questions, quiz, leçons, catégories) plutôt que saisis, pour ne jamais afficher de chiffre faux.
- **hreflang / multilingue** : laissé à Polylang / WPML (ils produisent déjà des hreflang réciproques + `x-default`) pour ne pas les doubler.
- **Thème sans les données** : les types de contenus, le SEO et le quiz sont dans le plugin (exigence du répertoire WordPress.org).

## Prochains lots (hors de cette livraison)

- Format « question à tiroirs ».
- Classement hebdomadaire et badges ; messages du moniteur à sa classe.
- Connexion Google / Apple / lien magique, module de paiement Stripe direct, adaptateurs Paid Memberships Pro / MemberPress.
- Mode hors ligne (service worker), intégration Search Console dans la santé SEO, assistant de démarrage, licence Pro.
- Lighthouse CI dans l'intégration continue.

## Développement

```sh
python3 bin/minify-css.py   # régénère les .min.css (thème et plugin)
sh bin/build-js.sh          # régénère les .min.js (esbuild)
vendor/bin/phpcs            # WordPress Coding Standards
```

Avec `SCRIPT_DEBUG` à `true`, WordPress charge les fichiers non minifiés.
