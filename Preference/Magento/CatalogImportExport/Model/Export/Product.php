<?php declare(strict_types=1);
/**
 * MageQuest - https://magequest.io
 * Copyright © MageQuest. All rights reserved.
 * See LICENSE.md file for details.
 */

namespace MageQuest\ProductExportDiscreteAttributeColumns\Preference\Magento\CatalogImportExport\Model\Export;

use Magento\CatalogImportExport\Model\Export\Product as ExportProduct;
use Magento\CatalogImportExport\Model\Import\Product as ImportProduct;
use Magento\ImportExport\Model\Import;
use Magento\Store\Model\Store;

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

    /**
     * Append multi row data
     *
     * Overridden purely to change the multi select separator to the pseudo separator (|)
     * so attributes are exported in the correct (and a re-importable) format
     *
     * @param array $dataRow
     * @param array $multiRawData
     * @return array
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    private function appendMultirowData(&$dataRow, $multiRawData)
    {
        $productId = $dataRow['product_id'];
        $productLinkId = $dataRow['product_link_id'];
        $storeId = $dataRow['store_id'];
        $sku = $dataRow[self::COL_SKU];
        $type = $dataRow[self::COL_TYPE];
        $attributeSet = $dataRow[self::COL_ATTR_SET];

        unset($dataRow['product_id']);
        unset($dataRow['product_link_id']);
        unset($dataRow['store_id']);
        unset($dataRow[self::COL_SKU]);
        unset($dataRow[self::COL_STORE]);
        unset($dataRow[self::COL_ATTR_SET]);
        unset($dataRow[self::COL_TYPE]);

        if (Store::DEFAULT_STORE_ID == $storeId) {
            $this->updateDataWithCategoryColumns($dataRow, $multiRawData['rowCategories'], $productId);
            if (!empty($multiRawData['rowWebsites'][$productId])) {
                $websiteCodes = [];
                foreach ($multiRawData['rowWebsites'][$productId] as $productWebsite) {
                    $websiteCodes[] = $this->_websiteIdToCode[$productWebsite];
                }
                $dataRow[self::COL_PRODUCT_WEBSITES] =
                    implode(Import::DEFAULT_GLOBAL_MULTI_VALUE_SEPARATOR, $websiteCodes);
                $multiRawData['rowWebsites'][$productId] = [];
            }
            if (!empty($multiRawData['mediaGalery'][$productLinkId])) {
                $additionalImages = [];
                $additionalImageLabels = [];
                $additionalImageIsDisabled = [];
                foreach ($multiRawData['mediaGalery'][$productLinkId] as $mediaItem) {
                    if ((int)$mediaItem['_media_store_id'] === Store::DEFAULT_STORE_ID) {
                        $additionalImages[] = $mediaItem['_media_image'];
                        $additionalImageLabels[] = $mediaItem['_media_label'];

                        if ($mediaItem['_media_is_disabled'] == true) {
                            $additionalImageIsDisabled[] = $mediaItem['_media_image'];
                        }
                    }
                }
                $dataRow['additional_images'] =
                    implode(Import::DEFAULT_GLOBAL_MULTI_VALUE_SEPARATOR, $additionalImages);
                $dataRow['additional_image_labels'] =
                    implode(Import::DEFAULT_GLOBAL_MULTI_VALUE_SEPARATOR, $additionalImageLabels);
                $dataRow['hide_from_product_page'] =
                    implode(Import::DEFAULT_GLOBAL_MULTI_VALUE_SEPARATOR, $additionalImageIsDisabled);
                $multiRawData['mediaGalery'][$productLinkId] = [];
            }
            foreach ($this->_linkTypeProvider->getLinkTypes() as $linkTypeName => $linkId) {
                if (!empty($multiRawData['linksRows'][$productLinkId][$linkId])) {
                    $colPrefix = $linkTypeName . '_';

                    $associations = [];
                    foreach ($multiRawData['linksRows'][$productLinkId][$linkId] as $linkData) {
                        if ($linkData['default_qty'] !== null) {
                            $skuItem = $linkData['sku'] . ImportProduct::PAIR_NAME_VALUE_SEPARATOR .
                                $linkData['default_qty'];
                        } else {
                            $skuItem = $linkData['sku'];
                        }
                        $associations[$skuItem] = $linkData['position'];
                    }
                    $multiRawData['linksRows'][$productLinkId][$linkId] = [];
                    asort($associations);
                    $dataRow[$colPrefix . 'skus'] =
                        implode(Import::DEFAULT_GLOBAL_MULTI_VALUE_SEPARATOR, array_keys($associations));
                    $dataRow[$colPrefix . 'position'] =
                        implode(Import::DEFAULT_GLOBAL_MULTI_VALUE_SEPARATOR, array_values($associations));
                }
            }
            $dataRow = $this->rowCustomizer->addData($dataRow, $productId);
        } else {
            $additionalImageIsDisabled = [];
            if (!empty($multiRawData['mediaGalery'][$productLinkId])) {
                foreach ($multiRawData['mediaGalery'][$productLinkId] as $mediaItem) {
                    if ((int)$mediaItem['_media_store_id'] === $storeId) {
                        if ($mediaItem['_media_is_disabled'] == true) {
                            $additionalImageIsDisabled[] = $mediaItem['_media_image'];
                        }
                    }
                }
            }
            if ($additionalImageIsDisabled) {
                $dataRow['hide_from_product_page'] =
                    implode(Import::DEFAULT_GLOBAL_MULTI_VALUE_SEPARATOR, $additionalImageIsDisabled);
            }
        }

        if (!empty($this->collectedMultiselectsData[$storeId][$productId])) {
            foreach (array_keys($this->collectedMultiselectsData[$storeId][$productId]) as $attrKey) {
                if (!empty($this->collectedMultiselectsData[$storeId][$productId][$attrKey])) {
                    $dataRow[$attrKey] = implode(
                    /** Preference Modification Start */
                    // Change to pseudo separator so multi select attributes in individual columns are
                    // correctly formatted and can be re-imported without further modification
                    // Old separator - Import::DEFAULT_GLOBAL_MULTI_VALUE_SEPARATOR
                        ImportProduct::PSEUDO_MULTI_LINE_SEPARATOR,
                        /** Preference Modification End */
                        $this->collectedMultiselectsData[$storeId][$productId][$attrKey]
                    );
                }
            }
        }

        if (!empty($multiRawData['customOptionsData'][$productLinkId][$storeId])) {
            $shouldBeMerged = true;
            $customOptionsRows = $multiRawData['customOptionsData'][$productLinkId][$storeId];

            if ($storeId != Store::DEFAULT_STORE_ID
                && !empty($multiRawData['customOptionsData'][$productLinkId][Store::DEFAULT_STORE_ID])
            ) {
                $defaultCustomOptions = $multiRawData['customOptionsData'][$productLinkId][Store::DEFAULT_STORE_ID];
                if (!array_diff($defaultCustomOptions, $customOptionsRows)) {
                    $shouldBeMerged = false;
                }
            }

            if ($shouldBeMerged) {
                $multiRawData['customOptionsData'][$productLinkId][$storeId] = [];
                $customOptions = implode(ImportProduct::PSEUDO_MULTI_LINE_SEPARATOR, $customOptionsRows);
                $dataRow = array_merge($dataRow, ['custom_options' => $customOptions]);
            }
        }

        if (empty($dataRow)) {
            return null;
        } elseif ($storeId != Store::DEFAULT_STORE_ID) {
            $dataRow[self::COL_STORE] = $this->_storeIdToCode[$storeId];
        }
        $dataRow[self::COL_SKU] = $sku;
        $dataRow[self::COL_ATTR_SET] = $attributeSet;
        $dataRow[self::COL_TYPE] = $type;

        return $dataRow;
    }

    /**
     * Get export data for collection
     *
     * Note: this method is overridden purely because it calls the private
     * method appendMultirowData() that is modified above
     *
     * @return array
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    protected function getExportData()
    {
        $exportData = [];
        try {
            $rawData = $this->collectRawData();
            $multirawData = $this->collectMultirawData();

            $productIds = array_keys($rawData);
            $stockItemRows = $this->prepareCatalogInventory($productIds);

            $this->rowCustomizer->prepareData(
                $this->_prepareEntityCollection($this->_entityCollectionFactory->create()),
                $productIds
            );

            $this->setHeaderColumns($multirawData['customOptionsData'], $stockItemRows);

            foreach ($rawData as $productId => $productData) {
                foreach ($productData as $storeId => $dataRow) {
                    if ($storeId == Store::DEFAULT_STORE_ID && isset($stockItemRows[$productId])) {
                        // phpcs:ignore Magento2.Performance.ForeachArrayMerge
                        $dataRow = array_merge($dataRow, $stockItemRows[$productId]);
                    }
                    $this->appendMultirowData($dataRow, $multirawData);
                    if ($dataRow) {
                        $exportData[] = $dataRow;
                    }
                }
            }
        } catch (\Exception $e) {
            $this->_logger->critical($e);
        }
        return $exportData;
    }
}
