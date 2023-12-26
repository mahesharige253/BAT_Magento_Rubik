# Navigate Commerce Core module for Magento 2

## How to install Navigate_Core module


### Composer Installation

Run the following command in Magento 2 root directory to install Navigate_Core module via composer.

#### Install

```
composer require navigate/module-core
php bin/magento setup:upgrade
php bin/magento setup:static-content:deploy -f
```

#### Update

```
composer update navigate/module-core
php bin/magento setup:upgrade
php bin/magento setup:static-content:deploy -f
```

Run below command if your store in the production mode:

```
php bin/magento setup:di:compile
```

### Manual Installation

If you prefer to install this module manually, kindly follow the steps described below - 

- Download the latest version [here](https://github.com/navigatecommerce/module-core/archive/refs/heads/main.zip) 
- Create a folder path like this `app/code/Navigate/Core` and extract the `main.zip` file into it.
- Navigate to Magento root directory and execute the below commands.

```
php bin/magento setup:upgrade
php bin/magento setup:static-content:deploy -f
```
