# Configuration

Une fois le module installé, il est nécessaire d'effectuer quelques réglages qui s'afficheront automatiquement à l'activation de celui-ci.

Le module est disponible aussi en anglais, il s'affichera en fonction de la langue de votre Wordpress.

![Capture d'écran de la page de configuration](https://res.cloudinary.com/cocolis-prod/image/upload/v1615223852/Documentation/woocommerce/110348164-a2527d80-8031-11eb-9a8e-89fe90a26a8b_tns4qm.png)

## Authentification

> Avant toute chose, vous devez avoir un compte développeur, vous trouverez plus d'information ici :
> [Demander un compte développeur](https://doc.cocolis.fr/docs/cocolis-api/docs/Tutoriel-impl%C3%A9mentation/Getting-Started.md#2-demander-un-compte-d%C3%A9veloppeur)

Renseignez par la suite les **champs d'authentification** qui se trouvent en bas de page.

## Environnements

Il existe **deux environnements**, l'environnement de test (**sandbox**) et l'environnement de **production**, vous pouvez en savoir plus [ici](https://doc.cocolis.fr/docs/cocolis-api/docs/Installation-et-utilisation/01-Environnements.md).

Choisissez en fonction de votre utilisation le mode désiré.

## Valeurs par défaut

Woocommerce ne nous permettant pas de définir des valeurs par défaut pour tous les produits mis en ligne, certaines valeurs sont à renseigner.

Vos fiches produits doivent en temps normal comporter : 
- Le poids
- La largeur
- La longueur 
- La hauteur 

Si certains produits sont absents de ces valeurs, le module ira chercher **les valeurs par défaut** définies dans la page de configuration du module, à appliquer pour les frais de livraison.

## Expédition

Pour calculer les frais de livraison, le module se base sur l'adresse d'expédition de votre entrepôt configuré dans Woocommerce nativement.

## Numéro de téléphone

Le numéro de téléphone du client et du vendeur est **obligatoire** pour le bon déroulé des livraisons.

Vous devez au préalable fournir un numéro de téléphone dans les paramètres du module dans le champ **Téléphone**.

## Configuration automatique des Webhooks

Après avoir effectué toute la configuration et validé, les Webhooks se configureront automatiquement sans action de votre part.

En cas de changement de nom de domaine ou d'adresse IP, il suffit de revalider le formulaire et les modifications seront prises automatiquement en compte.

Cela veut dire qu'à  chaque nouvelle étape de livraison, l'API de Cocolis vous enverra des "notifications" sous la forme de webhooks.