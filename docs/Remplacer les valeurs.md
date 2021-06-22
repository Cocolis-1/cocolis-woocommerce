---
tags: [remplacer, woocommerce, cocolis]
---

# Remplacer les valeurs de notre module

Par défaut nous prenons les valeurs définies dans WooCommerce ou nos valeurs pré-définies.

Cependant, vous pouvez changer les valeurs que nous prenons en appliquant des filtres avec votre propre module.

Voici la liste des filtres possibles :

`cocolis_store_production_mode` : Permet de définir si le module fait des appels API en production ou en sandbox

`cocolis_store_app_id` : Application ID de l'API

`cocolis_store_password` : Mot de passe API

`cocolis_store_width` : Largeur par défaut

`cocolis_store_length` : Longueur par défaut

`cocolis_store_height` : Hauteur par défaut

`cocolis_store_name` : Nom par défaut de la boutique

`cocolis_store_address` : Adresse d'expédition des commandes

`cocolis_store_address_2` : Adresse 2 d'expédition des commandes

`cocolis_store_city` : Ville d'expédition des commandes

`cocolis_store_postcode` : Code postal d'expédition des commandes

`cocolis_store_country` : Pays d'expédition des commandes

Vous pouvez utiliser la fonction ['add_filter'](https://developer.wordpress.org/reference/functions/add_filter/) de Wordpress pour changer les valeurs ci-dessus.

### Exemple de situation :

`add_filter('cocolis_store_country', change_country_function(), 10)`
