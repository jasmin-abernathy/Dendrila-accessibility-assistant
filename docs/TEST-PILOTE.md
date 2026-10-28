# Protocole de validation — Dendrila Accessibility Assistant (alpha)

**Statut : à exécuter sur un WordPress isolé, jamais en production.**
Ce protocole est un plan de test, pas un compte-rendu d'essais déjà réalisés.
Le plugin aide à corriger des contenus, mais ne produit pas de certificat RGAA/WCAG.

## Préparer le pilote

- WordPress 6.5 ou plus récent, PHP 8.1 ou 8.3 avec DOM/XML ; activer les journaux d'erreurs de test sans exposer les données des utilisateurs.
- Utiliser uniquement des pages, photos et comptes **fictifs** ; effectuer une sauvegarde de l'instance avant l'installation.
- Construire le ZIP via **Actions → ZIP WordPress installable → Run workflow** depuis la branche de cette PR ; télécharger l'artifact puis extraire le fichier `dendrila-accessibility-assistant.zip`.
- Les tests PHP doivent réussir pour fabriquer ce ZIP. **Plugin Check reste un contrôle indépendant** de préparation WordPress.org ; ses avertissements de métadonnées ne bloquent pas les essais privés, et aucun de ses résultats ne vaut autorisation de publier.
- Noter les versions exactes du WordPress, du constructeur (Elementor / Divi 4 / Divi 5), du navigateur et de l'extension.

## Parcours à effectuer

| ID | Préparation et action | Résultat attendu | Résultat |
| --- | --- | --- | --- |
| P01 | Installer/activer le ZIP en compte administrateur | Menu « Accessibilité » visible ; pas d'erreur PHP | Non testé |
| P02 | Enregistrer une page avec image sans attribut alt, champ sans label et saut h2→h4 | Observations compréhensibles, liens vers l'éditeur ; aucun changement automatique de la page | Non testé |
| P03 | Insérer une image avec `alt=""` volontairement décorative | Pas d'alerte « attribut alt absent » sur cette image | Non testé |
| P04 | Enregistrer plus de 70 images sans `alt` sur une même page de test | Les 70 premières observations restent visibles dans le diagnostic local ; contrôle indiqué comme incomplet, jamais « sain » | Non testé |
| P05 | Insérer une page avec contenu HTML trop volumineux (> 160 Ko) et une page ordinaire | Le passage continue sur les autres pages, comptabilise l'analyse incomplète, termine à 100 % sans promettre une couverture complète | Non testé |
| P06 | Créer un widget Elementor Texte dont le HTML dépasse 160 Ko | L'analyse du widget est explicitement incomplète ; aucun contenu Elementor modifié | Non testé |
| P07 | Créer une structure Elementor au-delà de 18 niveaux (fixture de test) | L'analyse limitée est signalée ; pas de fausse absence de problèmes | Non testé |
| P08 | Créer des widgets Elementor Image/Titre/Bouton, puis corriger l'alternative dans la médiathèque | L'observation évolue après enregistrement et nouvelle analyse ; aucune écriture dans `_elementor_data` par le plugin | Non testé |
| P09 | Construire une page Divi 4 avec module Image sans alt explicite | Avertissement **à vérifier** et non « non conforme » ; contenu Divi inchangé | Non testé |
| P10 | Construire une page Divi 5 avec Image et Texte | Détection expérimentale signalée sans revendiquer une lecture complète des attributs ; contenu inchangé | Non testé |
| P11 | Importer une nouvelle image informative puis passer par « Accessibilité » | Image marquée « à compléter » ; validation sans texte alternatif refusée ; alternative renseignée acceptée | Non testé |
| P12 | Déclarer une nouvelle image décorative | Alternative vide acceptée après choix explicite ; l'import d'autres médias n'est pas bloqué | Non testé |
| P13 | Examiner une ancienne image sans texte alternatif et une ancienne avec alternative | Aucun blocage rétroactif ; possibilité de les compléter sans altérer les autres métadonnées | Non testé |
| P14 | Effectuer la même analyse avec un compte ne possédant pas `manage_options` | Pas d'accès au diagnostic global ; respect des capacités d'édition et des nonces | Non testé |
| P15 | Naviguer dans l'interface d'administration au clavier, zoom 200 %, lecteur d'écran | Titres, boutons, radios, erreurs et progression utilisables ; documenter toute difficulté | Non testé |
| P16 | Désactiver l'extension, recharger les pages de test et leurs éditeurs | Aucune donnée de constructeur perdue ou modifiée ; aucune dépendance introduite | Non testé |

## Données à relever

Pour chaque scénario : `ID | version WP/PHP/constructeur | appareil/navigateur | réussi/échoué/non testé | symptôme observé | capture expurgée | lien vers issue`.

Ne pas envoyer de captures de contenus clients, de jetons WordPress, ni d'extraits de bases de données personnelles.

## Conditions de sortie

- **Essais privés** : P01–P05, P11–P14 validés sans erreur fatale, et les limitations sont comprises par la personne qui teste.
- **Compatibilité Elementor/Divi annoncée plus largement** : P06–P10 exécutés sur les versions réellement ciblées, avec vérification visuelle du HTML public.
- **Publication WordPress.org** : ne pas confondre résultats PHP, test de pilote, Plugin Check et revue humaine ; la licence, les métadonnées de répertoire, les traductions et le support restent à finaliser.

Un résultat « 100 % » décrit uniquement la fin des contrôles programmés. Il n'indique ni pourcentage de pages accessibles, ni score de conformité.
