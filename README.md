## Synopsis
An extension to add integration with 24Pay Payment Gateway

## Upload
Copy TwentyFourPay to [magento]/app/code

## Compile
php bin/magento setup:upgrade;
php bin/magento setup:di:compile;
php bin/magento setup:static-content:deploy -f;
php bin/magento cache:clean;
php bin/magento cache:flush

## Settings
Stores --> Configuration --> Sales -> Payment methods (https://[url]/admin/admin/system_config/edit/section/payment/key/[token]) --> 24Pay Payment Gateway
