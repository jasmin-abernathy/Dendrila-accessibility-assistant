# Sécurité et limites — Dendrila Accessibility Assistant

**Alpha expérimentale — pas de promesse d'audit exhaustif ni de conformité réglementaire.**

- Scanner entièrement local. Aucun appel HTTP, aucune API externe ni modification automatique des contenus ou métadonnées Divi/Elementor.
- Analyse HTML bornée à 160 Ko par fragment ; Elementor limité à 1,5 Mo de JSON, 500 éléments et 18 niveaux ; inventaire global limité à 100 publications. Les analyses volumineuses sont signalées comme limitées, pas « conformes ».
- Seuls les administrateurs ayant `manage_options` peuvent lancer l'analyse globale. POST protégé par nonce. Le panneau d'article nécessite `edit_post`.
- Le rapport global conserve des observations avec titres et IDs de contenus publiés. Aucun formulaire, e-mail, compte ou secret personnel n'est collecté. Eviter d'y faire figurer des URLs d'images avec query strings.
- Aucun scan de pages privées dans le tableau de bord (l'éditeur peut toutefois montrer un diagnostic du contenu courant à son auteur autorisé).
- Aucune correction automatique ou modification silencieuse ; accès à l'éditeur existant.
- Les contenus HTML stockés ne reflètent pas toujours le DOM réel après rendu, les widgets externes, les styles ou les composants chargés dynamiquement.

Pour signaler une vulnérabilité, utiliser un avis GitHub Security Advisory privé si disponible ; ne pas joindre de données utilisateurs dans une issue publique.

Avant publication générale : tests WordPress réels, PHP DOM, Elementor/Divi 4/5, audit de permissions, accessibilité clavier/lecteur d'écran, tests mobile et Plugin Check.
