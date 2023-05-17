---
tags: [remplacer, woocommerce, cocolis]
---

# Remplacer les valeurs de notre module

Par défaut nous prenons les valeurs définies dans WooCommerce ou nos valeurs pré-définies.

Cependant, vous pouvez changer les valeurs que nous prenons en appliquant des filtres avec votre propre module.

Voici la liste des filtres possibles :

> Le format ci-dessous est event(param1, param2)

`cocolis_store_production_mode($production_mode)` : Permet de définir si le module fait des appels API en production ou en sandbox

`cocolis_store_app_id($initial_value)` : Application ID de l'API

`cocolis_store_password($initial_value)` : Mot de passe API

`cocolis_store_width($width)` : Largeur par défaut

`cocolis_store_length($length)` : Longueur par défaut

`cocolis_store_height($height)` : Hauteur par défaut

`cocolis_store_name($initial_value, $product_id = null)` : Nom par défaut de la boutique

`cocolis_store_address($initial_value, $product_id = null)` : Adresse d'expédition des commandes

`cocolis_store_address_2($initial_value, $product_id = null)` : Adresse 2 d'expédition des commandes

`cocolis_store_city($initial_value, $product_id = null)` : Ville d'expédition des commandes

`cocolis_store_postcode($initial_value, $product_id = null)` : Code postal d'expédition des commandes

`cocolis_store_country($initial_value, $product_id = null)` : Pays d'expédition des commandes

<!-- theme: warning -->

> Les paramètres des filtres peuvent être null, il faut donc gérer le cas ! 
> add_filter( 'cocolis_store_address', function( $initial_value, $product_id = null){});

Vous pouvez utiliser la fonction ['add_filter'](https://developer.wordpress.org/reference/functions/add_filter/) de Wordpress pour changer les valeurs ci-dessus.

### Exemple de situation :

`add_filter('cocolis_store_country', change_country_function(), 10)`
