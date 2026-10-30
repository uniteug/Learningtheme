=== Learn & Drive Core ===
Contributors: uniteug
Tags: lms, quiz, education, permis, seo
Requires at least: 7.0
Tested up to: 7.1
Requires PHP: 8.1
Stable tag: 1.2.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Cours, quiz et examens blancs par pays, accès gratuit / premium, API REST et SEO intégré. Plugin compagnon du thème Learn & Drive.

== Description ==

* Contenus : cours, leçons, questions, quiz, fiches (panneaux, notions), infos & démarches, lexique. Actualités = articles WordPress, avec rubriques.
* Taxonomies : pays, catégorie (permis, matière, niveau…), thème officiel, type d'épreuve, niveau, matière, famille de fiches. Libellés renommables.
* URL en silos : /permis-b/, /permis-b/cours/{leçon}/, /permis-b/quiz/{thème}/, /permis-b/examen-blanc/, /permis-b/questions/{slug}/, /panneaux/{slug}/, /infos/{slug}/, /lexique/{slug}/ ; redirection 301 vers l'URL canonique.
* Moteur de quiz (Interactivity API, sans React ni jQuery côté visiteur) : vrai/faux, choix unique, choix multiple, réponse numérique avec tolérance, carte mémoire ; entraînement (correction immédiate), quiz thématique, examen blanc (tirage aléatoire réparti par thème, chronomètre total ou par question, retour arrière paramétrable, seuil de réussite) ; une question par écran, plein écran sans distraction, reprise automatique, temps majoré, texte agrandi, police adaptée à la dyslexie, signalement d'erreur, défi par lien.
* Règles d'examen par pays et par catégorie (métadonnées de termes) : ajouter un pays ne demande aucune ligne de code.
* Correction toujours côté serveur ; les bonnes réponses premium ne sont jamais envoyées sans droit d'accès.
* Accès : tout gratuit, tout payant ou mixte ; héritage site › catégorie › cours › leçon › contenu ; gratuit, inscription, premium ; début de leçon gratuit (bloc « Lire la suite »). Table d'accès indépendante du paiement : WooCommerce, codes d'accès (auto-écoles), ouverture manuelle.
* Progression des comptes : statistiques par thème, historique des examens blancs, prédiction « prêt pour l'examen », révision espacée des erreurs (J+1, J+3, J+7), séries de jours, export et suppression des données (RGPD).
* SEO intégré : titres et descriptions par modèles, canonique, robots (noindex sur recherche, sessions d'examen, questions trop minces), Open Graph / X avec images générées, JSON-LD (WebSite, EducationalOrganization, WebPage, BreadcrumbList, NewsArticle, Article, LearningResource, Course, ItemList, Quiz / Question / Answer, DefinedTerm, ProfilePage, VideoObject, contenu payant), sitemaps natifs enrichis, sitemap Google News 48 h, IndexNow, /llms.txt, blocage des robots d'IA, liens automatiques du lexique, ancres de titres, journal des 404 et redirections, écran « Santé SEO ». Compatible Yoast, Rank Math, SEOPress (fusion du graphe JSON-LD au lieu de le doubler).
* Médias : AVIF / WebP, qualité réglable, tailles utiles uniquement, noms de fichiers lisibles, façade vidéo YouTube / Vimeo, réécriture CDN, avatars locaux (aucune requête Gravatar).
* API REST /wp-json/learn/v1/ (questions, quiz, correction, sessions d'examen, progression, codes), clés d'API, limitation de débit, webhooks signés.
* Import / export CSV, JSON et JSON Lines (en continu, sans dépassement mémoire), commandes WP-CLI : wp learn import|export|demo|og.

== Installation ==

1. Activer le thème Learn & Drive puis ce plugin.
2. Learn & Drive › Import, export, démo › « Importer la démo » (ou `wp learn demo`).
3. Réglages › Permaliens : structure recommandée `/actualites/%postname%/`.

== Changelog ==

= 1.2.0 =
* Volets de présentation (vitrine / portail) et textes du bandeau d'accueil réglables.
* Blocs « Programmes phares » et « Pays » ; contenus liés en carrousel.
* Politique d'indexation : pages minces exclues de l'index et des sitemaps.
* Liens App Store / Google Play réglables.
* Démonstration Italie + Europe en français (patente B, A, AM ; guides et actualités sourcés).

= 1.1.0 =
* Espace auto-école : rôle Moniteur, classes avec code d'invitation, devoirs, suivi des élèves, export CSV.
* Assistant de démarrage (secteur, pays, apparence, accès, démo, pages).
* Révision hors ligne : application installable, service worker, page « hors ligne ».
* Formats de question : remettre dans l'ordre, association, image cliquable.
* Les apprenants arrivent sur « Mon compte » après connexion.
* Limitation de débit par compte pour les codes (une classe partage souvent la même adresse IP).

= 1.0.0 =
* Première version.
