# Dendrila Accessibility Assistant 🌱

Extension WordPress libre qui aide à **repérer et corriger** certains problèmes d'accessibilité éditoriale, sans prétendre certifier la conformité RGAA ou WCAG.

**0.1.0-alpha : expérimental, sans essai en production.**

## Première version

- Analyse locale du HTML enregistré : images sans `alt`, alternatives ressemblant à un nom de fichier, titres vides ou sauts de niveau, liens/boutons sans nom apparent, champs sans étiquette ou nom accessible.
- **Elementor** : lecture seule des widgets Image, Texte, Titre et Bouton dans `_elementor_data`; vérification prudente de l'alternative des images liées à la médiathèque WordPress. Pas de modification du JSON.
- **Divi 4** : repérage des modules Image via `et_pb_image` et indication de l'absence d'alternative **explicite** comme élément à vérifier, pas comme défaut confirmé.
- **Divi 5** : identification exploratoire de blocs de namespace Divi dans `parse_blocks()`, sans supposer la structure de leurs attributs ni revendiquer l'analyse de leurs modules.
- Diagnostic directement dans un panneau de l'éditeur WordPress (sur le **dernier enregistrement**, pas sur les changements en cours). Une analyse limitée ou illisible est explicitement signalée, jamais assimilée à un résultat sain.
- Tableau de bord : analyse volontaire des 100 contenus publics publiés les plus récents, liens pour corriger, observations avec explications en français. Une page impossible à examiner ne bloque pas la suite : elle est comptée comme échec. L’affichage atteint **100 % quand le passage est terminé**, même si certains contenus ne sont pas couverts.
- **Médiathèque** : explications sous les champs « texte alternatif », « légende » et « description » lorsque l’interface native le permet. Les nouvelles images sont marquées « à compléter » après leur import. Depuis le tableau de bord, la validation des informations impose de choisir « image informative » avec un texte utile, ou « décorative » avec une alternative volontairement vide.
- **Photos déjà présentes** : aucun blocage rétroactif. Les 100 images les plus récentes peuvent être contrôlées depuis le tableau de bord, avec un lien direct pour compléter les anciennes sans alternative. L’import initial WordPress reste possible : le contrôle obligatoire concerne la **validation guidée de l’image**, et non tous les modes de sauvegarde des médias.
- Aucun service tiers, aucune requête HTTP extérieure, aucune modification automatique de contenu, aucun nouveau rôle.

**Le « 100 % » indique uniquement que le contrôle demandé est terminé, jamais que toutes les pages sont accessibles.** Les analyses partielles, erreurs et contenus hors limite sont présentés séparément.

Les résultats sont des **observations**, pas un score de conformité. Les gros contenus Divi (plus de 1,5 Mo), les JSON Elementor illisibles et les fragments HTML inanalysables sont marqués comme analyses incomplètes. L'absence d'observations ne signifie pas « page accessible » ; le thème, les CSS, les contenus dynamiques, les interactions clavier, les lecteurs d'écran, les contrastes réellement calculés et les pages finales doivent être audités séparément.

## Installer sur un WordPress de test

WordPress 6.5+ et PHP 8.1+ (extension DOM/XML requise pour l'analyse HTML).

1. Depuis **GitHub Actions → ZIP WordPress installable → Run workflow**, produire l'archive (construction manuelle). Télécharger l'artefact terminé, puis **extraire le ZIP intérieur** `dendrila-accessibility-assistant.zip`.
2. Dans WordPress de test : **Extensions → Ajouter → Téléverser une extension**.
3. Ouvrir **Accessibilité** dans l'administration, cliquer sur **Analyser maintenant**.
4. Ouvrir une page avec observations ; consulter le panneau **Dendrila · Aide accessibilité**.
5. Corriger dans l'éditeur ou dans le constructeur concerné, enregistrer puis relancer l'analyse. Dans la section **Médiathèque : images à compléter**, choisir une image, préciser son rôle, puis valider son texte alternatif. Les anciennes images ne sont pas bloquées.
6. Vérifier le rendu réellement publié et l'utilisation au clavier et avec les aides techniques.

La fabrication du ZIP de test dépend des contrôles PHP, **pas** de la conformité des métadonnées à WordPress.org. Le workflow indépendant « WordPress Plugin Check » vérifie les règles de publication et peut rester en échec tant que la version est expérimentale. Cela ne signifie pas que le ZIP est prêt pour la production.

Voir le [protocole de test pilote](docs/TEST-PILOTE.md) avant d'annoncer une compatibilité réelle avec un constructeur.

**Ne pas installer d'abord en production.** Tester les versions spécifiques de Divi 4/5 et Elementor sur un pilote, notamment les modules natifs, les modèles globaux et la mise en cache.

## Développement

Exécuter `php tests/smoke.php` et `php tests/media.php` avec DOM/XML, puis `find . -name '*.php' -print0 | xargs -0 -n1 php -l`. La CI vérifie PHP 8.1 et 8.3. Les tests isolés ne remplacent pas une installation WordPress.

Avant de rendre le dépôt public, consulter [docs/PUBLICATION.md](docs/PUBLICATION.md) et [CONTRIBUTING.md](CONTRIBUTING.md).

Lire [docs/BUILDERS.md](docs/BUILDERS.md), [docs/ROADMAP.md](docs/ROADMAP.md), [SECURITY.md](SECURITY.md).

Licence GPL-2.0-or-later. Aucune dépendance à Dendrila Simple Edit.
