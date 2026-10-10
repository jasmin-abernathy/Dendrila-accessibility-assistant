=== Dendrila Accessibility Assistant ===
Contributors: juliane16
Tags: accessibility, alt text, elementor, divi, content
Requires at least: 6.5
Requires PHP: 8.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html

Aide à la correction éditoriale d'accessibilité sur WordPress, Elementor et Divi.

== Description ==

Dendrila Accessibility Assistant analyse en lecture seule des contenus WordPress enregistrés et fournit des conseils concrets en français.

La première version détecte certains attributs alt absents, textes alternatifs semblables à des fichiers, champs sans nom accessible apparent, titres vides, sauts de niveaux, et liens/boutons sans libellé. Un panneau dans l'éditeur et un tableau de bord permettent d'ouvrir rapidement les contenus à revoir.

**Elementor** : diagnostic partiel des widgets Image, Texte, Titre et Bouton (sans réécriture de son JSON).
**Divi 4** : diagnostic prudent des modules Image dans les shortcodes. **Divi 5** : repérage expérimental de certains blocs natifs, sans revendiquer l'analyse de leurs attributs.

**Attention : alpha uniquement.** Ce plugin n'effectue pas d'audit complet RGAA/WCAG, ne vérifie pas le rendu public ni les contrastes calculés et ne garantit pas l'accessibilité d'un site. Aucun service distant nécessaire.

== Installation ==

Installer le ZIP produit par le workflow manuel GitHub Actions sur un WordPress de test. Ouvrir Accessibilité dans l'administration, puis analyser les contenus ou consulter le panneau d'un article.

== Frequently Asked Questions ==

= Est-ce une certification RGAA ou WCAG ? =

Non. Les constats automatisés couvrent une portion limitée des besoins.

= La mise en page Divi ou Elementor est-elle modifiée ? =

Non. Le plugin lit certaines données enregistrées et n'écrit ni dans les contenus ni dans les métadonnées des constructeurs.

== Changelog ==

= 0.1.0-alpha =
* Analyse éditoriale des attributs, titres, liens et champs.
* Diagnostics Elementor et Divi 4/5, avec périmètre limité.
* Tableau de bord et panneau d'aide en édition.
