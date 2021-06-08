---
tags: [intro, woocommerce, cocolis]
---

# Introduction

Bienvenue dans la documentation officielle du module Woocommerce de **Cocolis**.
Ce module vous permet d'installer facilement notre solution de livraison sur votre site et de la proposer à tous vos clients sans frais supplémentaires.

Vous pouvez signaler des bugs sur cette [page](https://github.com/Cocolis-1/cocolis-woocommerce/issues).

# Installation

Tout d’abord, vous devez avoir le plugin WooCommerce installé sur votre site pour pouvoir
utiliser le plugin Cocolis pour WooCommerce.

Les versions minimales supportées sont les suivantes:

- **PHP 5.6 ou supérieure**,
- **WordPress 4.7 ou supérieure**
- **WooCommerce 5.3.3 ou supérieure**

Le plugin peut s’installer comme n’importe quel plugin WordPress depuis la section Extensions du site.

**Il existe deux manières d'installer le module :**

- Par le catalogue d'extensions inclus dans Wordpress :

1. Accédez à votre espace d'administration Wordpress
   (Généralement le lien de votre site suivi de **/wp-admin** par exemple : https://cocolis.fr/wp-admin)

2. Cliquez sur **Extensions** puis **Ajouter**

3. Recherchez **Cocolis Woocommerce** dans la barre de recherche

4. Installer maintenant

5. Activez ensuite l'extension dans **Extensions installées**

- Télerverser l'extension manuellement :

1. Télécharger la dernière release [ici](https://github.com/Cocolis-1/cocolis-woocommerce/releases) (cocolis.zip)

2. Accédez à votre espace d'administration Wordpress
   (Généralement le lien de votre site suivi de **/wp-admin** par exemple : https://cocolis.fr/wp-admin)

3. Cliquez sur **Extensions** puis **Ajouter**

4. **Téléverser maintenant** et suivez les étapes

5. Activez ensuite l'extension dans **Extensions installées**

# Erreurs

Par défaut pour éviter tout désagrément à vos clients, les erreurs de notre module sont cachées.
Si notre équipe technique venait à avoir besoin de précisions vous pouvez ajouter cette ligne dans le fichier de configuration Wordpress (**wp_config.php**) :

`define('WP_DEBUG_LOG', true);`

Par la suite vous trouverez les logs dans le fichier **debug.log** dans le dossier **wp-content** de votre CMS.

Chaque erreur de notre module commençent par `Cocolis ERROR`.
