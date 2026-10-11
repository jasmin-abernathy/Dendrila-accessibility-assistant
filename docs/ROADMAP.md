# Feuille de route

## Lot 0 — Aide éditoriale ✅
- [x] Plugin WordPress, tableau de bord français et panneau d'aide dans l'éditeur.
- [x] Analyse HTML bornée de quelques erreurs ou risques classiques.
- [x] Lectures partielles d'Elementor et Divi 4 ; présence de blocs Divi 5 avec niveau de confiance limité.
- [x] Aucune écriture dans les constructeurs.
- [x] Tests PHP 8.1 et 8.3.
- [x] Signaler distinctement les analyses impossibles ou tronquées, avec limite de traitement Divi.
- [ ] Essai sur un WordPress pilote réel.

## Lot 1 — Couverture et fiabilité
- [ ] Prise en charge des blocs Gutenberg et motifs synchronisés avec résolution de contexte.
- [ ] Analyse précise des réglages Elementor Pro, templates, image alt réellement rendue.
- [ ] Fixtures de Divi 5 et de Divi 4 ; contrôles sur modules Texte, Bouton, Image et Contact.
- [ ] Repérage des contrôles de formulaire et des libellés dans le DOM final.
- [ ] Affichage d'une suggestion de correction directement au bon champ dans chaque constructeur.

## Lot 2 — Analyse de rendu
- [ ] Audit navigateur du DOM effectivement rendu, contrastes calculés et état du focus.
- [ ] Parcours assistés de navigation clavier et revue humaine.
- [ ] Références explicites à WCAG/RGAA pour chaque règle réellement vérifiée ; ne pas attribuer un score global trompeur.
- [ ] Tests d'accessibilité du plugin lui-même, mobile et lecteur d'écran.

## Lot 3 — Publication
- [ ] WordPress de test avec Elementor et Divi 4/5, sécurité et Plugin Check.
- [ ] Explications utilisateur validées et classification des faux positifs.
- [ ] Vérification de la description principale `readme.txt` avant publication WordPress.org.
- [ ] Documentation, retours de test et version stable.
