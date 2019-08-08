## Obv Payment Gateway for Magento 2

This extension allows you to use ObvPayment as payment gateway in your Magento 2 store.

## Installing via [Composer](https://getcomposer.org/)

```bash
composer require obvpayment/obvpayment-magento-2
php bin/magento module:enable ObvPayment --clear-static-content
php bin/magento setup:upgrade
```

Enable and configure ObvPayment in Magento Admin under `Stores -> Configuration -> Payment Methods -> ObvPayment Payment Gateway`.

## Configuration

  - **Enabled:** Mark this as "Yes" to enable this plugin.

  - **Title:** Test to be shown to user during checkout. For example: "Pay using DB/CC/NB/Wallets"

  - **Checkout Label:** This is the label users will see during checkout, its default value is "Pay using ObvPayment". You can change it to something more generic like "Pay using Credit/Debit Card or Online Banking".

## Support

For any issue send us an email to info@99sarms.com