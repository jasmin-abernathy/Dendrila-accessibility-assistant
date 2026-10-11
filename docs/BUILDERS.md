# Divi et Elementor — compatibilité non destructive

Conformément aux conventions déjà utilisées dans Dendrila Simple Edit, les constructeurs sont **lus, jamais réécrits**.

## Elementor

- Lire la métadonnée `_elementor_data` du contenu actuellement inspecté. Si elle est volumineuse ou illisible, marquer le diagnostic comme incomplet et ne pas annoncer « zéro problème » comme une preuve d'accessibilité.
- Parcourir en lecture seule `elements` et les widgets au maximum 18 niveaux/500 éléments.
- Lire uniquement les réglages connus : `widgetType=image` (image ID média et `_wp_attachment_image_alt`), `heading.title`, `text-editor.editor`, `button.text`.
- Ne pas présenter un média sans alternative comme un échec assuré : il peut être décoratif ou modifié par le rendu. Ne pas considérer un nom de widget comme une preuve de rendu public.
- Ne pas éditer `_elementor_data`, ni les templates globaux, classes ou CSS.

## Divi 4

- Les shortcodes `[et_pb_image ...]` sont examinés dans le `post_content` enregistré, dans la limite de 1,5 Mo ; au-delà, le diagnostic est incomplet.
- L'absence d'attribut `alt` explicite nécessite une vérification, **pas une correction automatique** : Divi peut utiliser la valeur de la médiathèque.
- Les modules contact, bouton, blurb, contenus dynamiques et les modèles Divi 4 ne sont pas encore audités.

## Divi 5

- Le contenu peut comporter des blocs natifs et attributs spécifiques.
- Un namespace probable `divi/` ou `et/` dans `parse_blocks` est seulement une indication de présence, non une garantie d'identification exhaustive.
- Ne pas lire ni transformer les attributs internes sans une fixture réelle vérifiée de la version ciblée.
- Tester obligatoirement un pilote Divi 5 avant d'affirmer une compatibilité complète.

## Les tests indispensables sur pilote

Créer une page de test Elementor (Image, Titre, Texte, Bouton), une Divi 4 (module Image) et une Divi 5 (module Image natif), conserver un export avant/après, lancer le diagnostic, puis vérifier que les données des constructeurs et le rendu n'ont pas changé. Comparer ensuite le DOM public avec les observations.

Cette version ne cherche pas à corriger automatiquement les contenus ; elle pointe vers l'éditeur natif.
