# MageQuest_ProductExportDiscreteAttributeColumns

Usable product CSV export files.

<div>
    <img src="https://img.shields.io/badge/magento-2.4-orange.svg?logo=magento&longCache=true&style=for-the-badge" alt="Magento 2.4"/>
    <img src="https://img.shields.io/packagist/v/magequest/magento2-module-product-export-discrete-attribute-columns?style=for-the-badge" alt="Packagist Version">
    <img src="https://img.shields.io/badge/License-MIT-blue.svg?longCache=true&style=for-the-badge" alt="MIT License"/>
</div>

## Overview
A Magento 2 module that exports product attributes to discrete columns when exporting to CSV.

## Features
* Outputs all custom (non default/system) attributes in discrete columns when exporting products to CSV
* Removes the 'additional_attributes' column that combines all custom attribute output by default
* Ensures multi-select attribute values are correctly separated when output to their own column
                
## Why?
Because having all non default/system attributes concatenated into a single column in a CSV means data can't be easily sorted, filtered, analysed or modified.

Product imports already (have always) allowed for each attributes data to be provided in their own column, exports should be no different.  

## Installation
```
composer require magequest/magento2-module-product-export-discrete-attribute-columns
bin/magento module:enable MageQuest_ProductExportDiscreteAttributeColumns
bin/magento setup:upgrade
```

## Compatibility
Magento Open Source / Adobe Commerce 2.4.x

## Contributing
Issues and pull requests welcomed.
