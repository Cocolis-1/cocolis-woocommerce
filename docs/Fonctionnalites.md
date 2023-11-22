---
tags: [création, annulations, woocommerce, cocolis]
---

# Création des annonces automatiquement

Par défault la création de l'annonce chez cocolis qui va délencher le demande de disponibilités arrive quand la commande passe en status "En cours" (généralement au webhook du partenaire de paiement).

> En cas de paiement sans webhook (Chèque, virement), il faut que l'administrateur du site passe bien la commande "En cours"


A ce moment là, l'annonce est créée chez cocolis.fr et les demandes de disponibilités sont déclenchées. Un certain nombre d'informations sont rajoutées dans la Note de la commande : 

![Capture d'écran création](https://res.cloudinary.com/cocolis-prod/image/upload/v1700649572/CleanShot_2023-11-22_at_11.38.41_2x_zswrjw.png)


# Créer une annonce manuellement

Depuis la version **1.0.11** de notre module Woocommerce, nous avons implémenté la possibilité de créer manuellement une annonce chez Cocolis sans changer le status d'une commande existante.

Pour ce faire, **si le client à choisi la livraison par Cocolis**, une section apparaîtra de cette façon sur le suivi de commande administrateur :

![Capture d'écran création](https://res.cloudinary.com/cocolis-prod/image/upload/v1633085081/Documentation/woocommerce/create%20ride%20manually.png)

Vous pouvez cliquer sur ce bouton pour forcer la création 🙂

# Annulations des livraisons

Depuis la version **1.0.10** de notre module Woocommerce nous avons implémenté les annulations.
Lorsque vous effectuez un remboursement **total** à un client depuis l'administration de votre site, Cocolis se chargera d'annuler la livraison.

![Capture d'écran annulations](https://res.cloudinary.com/cocolis-prod/image/upload/v1632331499/Documentation/woocommerce/refund_woocommerce.png)
