# Cocolis.fr plugin for Woocommerce

This Cocolis module adapted for WooCommerce allows you to easily install our shipping solution on your site and offer it to all your customers at no extra cost.

**Description**

Cocolis is the 1st site of parcel sharing. It connects people to allow the transport of objects at a lower cost.

By using Cocolis, you win on both sides: the driver earns money and the sender saves money.

⌚ It's convenient: the location and time of delivery are defined between you.

🌳 Green: one car to transport the packages of several people!

💰 And cheap: you only pay a contribution to the carrier's road expenses.

Cocolis makes cotransportation (newness of the law "LOM"). It is the ideal solution for a home delivery, a transport, large, heavy or fragile parcels. Or simply to find a carrier. Already more than 300 000 members on Cocolis ! A smart and cheap logistic solution for everyone!

Our module allows you to offer our free delivery solution to your customers.

**Documentation**

A question about the use of our module?

Go [here](https://doc.cocolis.fr "Documentation de Cocolis") to get help.

**Launch project locally**

Install composer on your machine

```
brew install composer
```

Install dependencies

```
composer install
```

Use docker to run:

```
docker compose up -d
```

To restart WordPress application:

```
docker-compose restart wordpress
```

and go to [http://localhost:8189/](http://localhost:8189/)

If you want to log in as admin use these test credentials defined in the `Dockerfile`

```
    WORDPRESS_ADMIN_USERNAME='admin' \
    WORDPRESS_ADMIN_PASSWORD='admin123' \
```

Go to [http://127.0.0.1:8189/wp-admin/plugins.php](http://127.0.0.1:8189/wp-admin/plugins.php) and you can activate the Cocolis extension

**Release**

To create a release, just run :

```
composer run wp-package
```

**Troubleshooting**

Error message: "The address entered in the Woocommerce settings is not properly configured to fully use the Cocolis module." when saving extension settings.

Resolution: Go to wp-admin/admin.php?page=wc-settings and set a Store Address compatible with Cocolis module

Error: Cocolis does not appear as a shipping option even after entering all required settings (app_id, passwords, default sizes, ...).

Resolution: Be sure to activate automatic payment in the settings of your cocolis account [https://www.cocolis.fr/profile/payment-methods](https://www.cocolis.fr/profile/payment-methods)

Error: I do not see a Cocolis ride related to an order already passed on my website when I go to [https://cocolis.fr/rides/owner](https://cocolis.fr/rides/owner)

Resolution: Be sure that the phone field in the payment page is required and filled and no information about the customer is missing. Check logs for any Cocolis Error like `Cocolis ERROR : Cocolis\\Api\\Errors\\UnprocessableEntityException`
