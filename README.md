# Dendrila Accessibility Assistant 🌱

Extension WordPress libre qui aide à **repérer et corriger** certains problèmes d'accessibilité éditoriale, sans prétendre certifier la conformité RGAA ou WCAG.

**0.1.0-alpha : expérimental, sans essai en production.**

## Première version

- Analyse locale du HTML enregistré : images sans `alt`, alternatives ressemblant à un nom de fichier, titres vides ou sauts de niveau, liens/boutons sans nom apparent, champs sans étiquette ou nom accessible.
- **Elementor** : lecture seule des widgets Image, Texte, Titre et Bouton dans `_elementor_data`; vérification prudente de l'alternative des images liées à la médiathèque WordPress. Pas de modification du JSON.
- **Divi 4** : repérage des modules Image via `et_pb_image` et indication de l'absence d'alternative **explicite** comme élément à vérifier, pas comme défaut confirmé.
- **Divi 5** : identification exploratoire de blocs de namespace Divi dans `parse_blocks()`, sans supposer la structure de leurs attributs ni revendiquer l'analyse de leurs modules.
- Diagnostic directement dans un panneau de l'éditeur WordPress (sur le **dernier enregistrement**, pas sur les changements en cours).
- Tableau de bord : analyse volontaire des 100 contenus publics publiés les plus récents, liens pour corriger, observations avec explications en français.
- Aucun service tiers, aucune requête HTTP extérieure, aucune modification automatique de contenu, aucun nouveau rôle.

Les résultats sont des **observations**, pas un score de conformité. L'absence d'observations ne signifie pas « page accessible » ; le thème, les CSS, les contenus dynamiques, les interactions clavier, les lecteurs d'écran, les contrastes réellement calculés et les pages finales doivent être audités séparément.

## Installer sur un WordPress de test

WordPress 6.5+ et PHP 8.1+ (extension DOM/XML requise pour l'analyse HTML).

1. Depuis **GitHub Actions → ZIP WordPress installable → Run workflow**, produire l'archive (construction manuelle). Télécharger l'artefact terminé, puis **extraire le ZIP intérieur** `dendrila-accessibility-assistant.zip`.
2. Dans WordPress de test : **Extensions → Ajouter → Téléverser une extension**.
3. Ouvrir **Accessibilité** dans l'administration, cliquer sur **Analyser maintenant**.
4. Ouvrir une page avec observations ; consulter le panneau **Dendrila · Aide accessibilité**.
5. Corriger dans l'éditeur ou dans le constructeur concerné, enregistrer puis relancer l'analyse.
6. Vérifier le rendu réellement publié et l'utilisation au clavier et avec les aides techniques.

**Ne pas installer d'abord en production.** Tester les versions spécifiques de Divi 4/5 et Elementor sur un pilote, notamment les modules natifs, les modèles globaux et la mise en cache.

## Développement

Exécuter `php tests/smoke.php` avec DOM/XML, puis `find . -name '*.php' -print0 | xargs -0 -n1 php -l`. La CI vérifie PHP 8.1 et 8.3. Les tests isolés ne remplacent pas une installation WordPress.

Lire [docs/BUILDERS.md](docs/BUILDERS.md), [docs/ROADMAP.md](docs/ROADMAP.md), [SECURITY.md](SECURITY.md).

Licence GPL-2.0-or-later. Aucune dépendance à Dendrila Simple Edit.
