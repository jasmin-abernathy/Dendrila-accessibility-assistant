# Dendrila Accessibility Assistant — règles

- Lire les conventions de `jasmin-abernathy/repo-factory` avant toute intervention ; documenter les erreurs récupérables dans ses `errors/`.
- Ne jamais annoncer un score de conformité RGAA/WCAG à partir de fragments HTML ou de paramètres du constructeur.
- **Divi et Elementor sont couverts dès le premier lot** avec des contrôles en lecture seule ; distinguer les versions et limites.
- Ne jamais modifier automatiquement la mise en page, le JSON Elementor ou les attributs/blocs Divi.
- Scanner uniquement un nombre borné de publications et de données constructeur ; pas de requêtes HTTP extérieures.
- L'analyse globale requiert `manage_options` + nonce ; le panneau de contenu requiert `edit_post`.
- L'UX doit donner une piste de correction concrète, pas seulement un décompte d'erreurs.
- Les tests PHP ne remplacent pas un pilote WordPress réel ; ne rien déployer sur un site client sans demande explicite.
- Un commit propre par lot, pas de références aux assistants de génération.
