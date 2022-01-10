<?php declare(strict_types=1);
/**
 * MageQuest - https://magequest.io
 * Copyright © MageQuest. All rights reserved.
 * See LICENSE.md file for details.
 */

namespace MageQuest\ProductExportDiscreteAttributeColumns\Preference\Magento\CatalogImportExport\Model\Export;

use Magento\CatalogImportExport\Model\Export\Product as ExportProduct;

class Product extends ExportProduct
{
    public function _getHeaderColumns(): array
    {
        $this->addNonDefaultAttributeColumns();
        $this->removeAdditionalAttributesColumn();

        return parent::_getHeaderColumns();
    }

    protected function addNonDefaultAttributeColumns(): void
    {
        $this->_headerColumns = array_unique(array_merge(
            $this->_headerColumns,
            $this->_getExportAttrCodes()
        ));
    }

    protected function removeAdditionalAttributesColumn(): void
    {
        $additionalAttributesKey = array_search(self::COL_ADDITIONAL_ATTRIBUTES, $this->_headerColumns);
        if ($additionalAttributesKey !== false) {
            unset($this->_headerColumns[$additionalAttributesKey]);
        }
    }
}
