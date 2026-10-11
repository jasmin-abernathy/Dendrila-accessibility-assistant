# Contribuer à Dendrila Accessibility Assistant

Merci pour les retours, les correctifs et les cas de test 🌱

## Avant une proposition

- Ouvrir une issue pour décrire le comportement observé, la version de WordPress/PHP et les étapes de reproduction, sans publier de données personnelles ni de secrets.
- Pour une faille de sécurité, utiliser les **GitHub Security Advisories** privés si disponibles, plutôt qu'une issue publique.
- Proposer des changements ciblés sur une branche et ouvrir une pull request. Documenter les cas non pris en charge et éviter de promettre une compatibilité non testée.
- Exécuter les vérifications PHP : `php tests/smoke.php` et `find . -name '*.php' -print0 | xargs -0 -n1 php -l`.
- Préserver les droits WordPress, les jetons de requêtes et les échappements des sorties HTML.

## Points importants

Tester toute évolution sur Gutenberg, Elementor et Divi en lecture seule. Ne pas faire de promesse de conformité RGAA/WCAG à partir du code enregistré. Ne jamais réécrire les structures des constructeurs dans une contribution de diagnostic.

**Statut actuel : version alpha.** Un test automatisé vert ne remplace pas un essai sur un WordPress pilote ni une revue de sécurité avant utilisation réelle.

Licence et redistribution : consulter `LICENSE`.
