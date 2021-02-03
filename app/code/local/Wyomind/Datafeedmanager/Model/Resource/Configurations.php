<?php

/**
 * Copyright © 2018 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */
class Wyomind_Datafeedmanager_Model_Resource_Configurations extends Mage_Core_Model_Resource_Db_Abstract
{

    public $resource;
    public $read;
    public $options = 'ISNULL(options)';
    public $notLike = "AND url.target_path LIKE '%category%'";
    public $concat = 'GROUP_CONCAT';
    public $where = '';
    public $collection;

    public function _construct()
    {
        // Note that the datafeedmanager_id refers to the key field in your database table.
        $this->_init('datafeedmanager/configurations', 'feed_id');

        $this->resource = Mage::getSingleton('core/resource');
        $this->read = $this->resource->getConnection('core_read');

        if (version_compare(Mage::getVersion(), '1.6.0', '<')) {
            $this->options = "options=''";
        }

        if (Mage::getStoreConfig('datafeedmanager/system/urlrewrite') == 1) {
            $this->notLike = "AND url.target_path NOT LIKE '%category%'";
            $this->concat = 'MAX';
        } else {
            $this->notLike = "AND url.target_path LIKE '%category%'";
            $this->concat = 'GROUP_CONCAT';
        }
    }

    /**
     * Prepare product collection with store/visibility/attributes/categories filters
     *
     * @param string $storeId
     * @param array $typeIdFilter
     * @param array $visibilityFilter
     * @param array $attributeSetsFilter
     * @param array $attributes
     * @param array $attributesFilter
     * @param string $categoriesFilterList
     * @param string $categoryFilter
     * @param boolean $joinType
     * @param string $manageStock
     * @param string $websiteId
     * @return Wyomind_Datafeedmanager_Model_Product_Collection
     */
    public function prepareProductCollection($storeId, $typeIdFilter, $visibilityFilter,
                                             $attributeSetsFilter, $attributes, $attributesFilter, $categoriesFilterList,
                                             $categoryFilter, $joinType, $manageStock, $websiteId
    )
    {
        $tableCsi = $this->resource->getTableName('cataloginventory_stock_item');
        $tableCur = $this->resource->getTableName('core_url_rewrite');
        $tableEur = $this->resource->getTableName('enterprise_url_rewrite');
        $tableCcp = $this->resource->getTableName('catalog_category_product');
        $tableCcpi = $this->resource->getTableName('catalog_category_product_index');
        $tableCpip = $this->resource->getTableName('catalog_product_index_price');
        $tableCpsl = $this->resource->getTableName('catalog_product_super_link');

        $condition = array(
            'eq' => "= '%s'",
            'neq' => "!= '%s'",
            'gteq' => ">= '%s'",
            'lteq' => "<= '%s'",
            'gt' => "> '%s'",
            'lt' => "< '%s'",
            'like' => "like '%s'",
            'nlike' => "not like '%s'",
            'null' => "is null",
            'notnull' => "is not null",
            'in' => "in (%s)",
            'nin' => "not in(%s)",
        );

        $where = '';


        $this->collection = Mage::getModel('datafeedmanager/product_collection')->getCollection()->addStoreFilter($storeId);

        if (Mage::getStoreConfig('datafeedmanager/system/disabled')) {
            $this->collection->addFieldToFilter('status', array('gteq' => 1));
        } else {
            $this->collection->addFieldToFilter('status', 1);
        }
        $this->collection->addAttributeToFilter('type_id', array('in' => $typeIdFilter));
        $this->collection->addAttributeToFilter('visibility', array('in' => $visibilityFilter));

        if ($attributeSetsFilter[0] != '*') {
            $this->collection->addAttributeToFilter('attribute_set_id', array('in' => $attributeSetsFilter));
        }

        $this->collection->addAttributeToSelect($attributes, $joinType);

        $tempFilter = $this->prepareAttributesFilter($attributesFilter, $condition, $manageStock);

        if (count($tempFilter)) {
            $this->collection->addFieldToFilter($tempFilter);
        }

        $this->collection->getSelect()->joinLeft($tableCsi . ' AS stock', 'stock.product_id=e.entity_id', array(
            'qty' => 'qty',
            'is_in_stock' => 'is_in_stock',
            'manage_stock' => 'manage_stock',
            'use_config_manage_stock' => 'use_config_manage_stock',
            'use_config_backorders' => 'use_config_backorders',
            'min_qty' => 'min_qty',
            'min_sale_qty' => 'min_sale_qty'
        ));

        if (version_compare(Mage::getVersion(), '1.13.0', '>=')) {
            $this->collection->getSelect()->joinLeft(
                $tableEur . ' AS url', 'url.value_id=IF(at_url_key.value_id,at_url_key.value_id,at_url_key_default.value_id) '
                . $this->notLike . ' AND is_system=1 ', array('request_path' => $this->concat . '(DISTINCT url.request_path)')
            );
        } else {
            $this->collection->getSelect()->joinLeft(
                $tableCur . ' AS url', 'url.product_id=e.entity_id ' . $this->notLike . ' AND is_system=1 AND '
                . $this->options . ' AND url.store_id=' . $storeId, array('request_path' => $this->concat . '(DISTINCT request_path)')
            );
        }

        if (Mage::getStoreConfig('datafeedmanager/system/use_parent_categories')) {
            $this->collection->getSelect()->joinLeft(
                $tableCpsl . ' AS cpsl', 'cpsl.product_id=e.entity_id ', array('parent_id' => 'parent_id')
            );
        }

        $or = null;
        if (Mage::getStoreConfig('datafeedmanager/system/use_parent_categories')) {
            $or = "OR (categories_index.category_id=categories_parent.category_id "
                . "AND categories_index.product_id=categories_parent.product_id )";
        }

        if ($categoriesFilterList[0] != '*') {
            $filter = implode(',', $categoriesFilterList);

            $in = ($categoryFilter) ? 'IN' : 'NOT IN';

            if (version_compare(Mage::getVersion(), '1.12.0', '<=')) {


                $this->collection->getSelect()->joinLeft(
                    $tableCcp . ' AS categories', 'categories.product_id=e.entity_id', array()
                );
                if (Mage::getStoreConfig('datafeedmanager/system/use_parent_categories')) {
                    $this->collection->getSelect()->joinLeft(
                        $tableCcp . ' AS categories_parent', 'categories_parent.product_id=cpsl.parent_id'
                    );
                }
                if ($in == "IN") {
                    $filter = ' AND categories_index.category_id ' . $in . ' (' . $filter . ')';

                    $this->collection->getSelect()->joinInner(
                        $tableCcpi . ' AS categories_index', '((categories_index.category_id=categories.category_id '
                        . 'AND categories_index.product_id=categories.product_id) ' . $or . ') '
                        . 'AND categories_index.store_id=' . $storeId . ' ' . $filter, array('categories_ids' => 'GROUP_CONCAT( DISTINCT categories_index.category_id)')
                    );
                } else {
                    $having = array();
                    foreach ($categoriesFilterList as $filter) {
                        $having[] = "NOT FIND_IN_SET($filter,categories_ids)";
                    }

                    $this->collection->getSelect()->joinInner(
                        $tableCcpi . ' AS categories_index', '((categories_index.category_id=categories.category_id '
                        . 'AND categories_index.product_id=categories.product_id) ' . $or . ') '
                        . 'AND categories_index.store_id=' . $storeId . ' ', array('categories_ids' => 'GROUP_CONCAT( DISTINCT categories_index.category_id)')
                    );
                }

            } else {
                $filter = ' AND categories.category_id ' . $in . ' (' . $filter . ')';
                $this->collection->getSelect()->joinInner(
                    $tableCcp . ' AS categories', 'categories.product_id=e.entity_id ' . $filter, array('categories_ids' => 'GROUP_CONCAT( DISTINCT categories.category_id)')
                );
            }
        } else {
            $this->collection->getSelect()->joinLeft($tableCcp . ' AS categories', 'categories.product_id=e.entity_id');
            if (Mage::getStoreConfig('datafeedmanager/system/use_parent_categories')) {
                $this->collection->getSelect()->joinLeft(
                    $tableCcp . ' AS categories_parent', 'categories_parent.product_id=cpsl.parent_id'
                );
            }

            $this->collection->getSelect()->joinLeft(
                $tableCcpi . ' AS categories_index', '((categories_index.category_id=categories.category_id '
                . 'AND categories_index.product_id=categories.product_id) ' . $or . ') '
                . 'AND categories_index.store_id=' . $storeId, array('categories_ids' => 'GROUP_CONCAT(DISTINCT categories_index.category_id)')
            );
        }

        if (version_compare(Mage::getVersion(), '1.4.0', '>=')) {
            $this->collection->getSelect()->joinLeft(
                $tableCpip . ' AS price_index', 'price_index.entity_id=e.entity_id AND customer_group_id=0 '
                . 'AND price_index.website_id=' . $websiteId, array(
                    'min_price' => 'min_price',
                    'max_price' => 'max_price',
                    'tier_price' => 'tier_price',
                    'final_price' => 'final_price'
                )
            );
        }
        if (!empty($having)) {
            $this->collection->getSelect()->having(implode(" AND ",$having));
        }
        if (!empty($where)) {
            $this->collection->getSelect()->where($where);
        }

        $this->collection->getSelect()->group(array('e.entity_id'))->order('e.entity_id');

        return $this->collection;
    }

    /**
     * Get currency rates
     *
     * @param array $currency
     * @return array
     */
    public function getCurrencyRates($currency)
    {
        $tableCcp = $this->resource->getTableName('directory_currency_rate');
        $select = $this->read->select()->from($tableCcp)->where('currency_from=\'' . $currency . '\'');
        $currencyRates = $this->read->fetchAll($select);

        return $currencyRates;
    }

    /**
     * Get attribute labels
     *
     * @param string $storeId
     * @return array
     */
    public function getAttributeLabels($storeId)
    {
        $tableEaov = $this->resource->getTableName('eav_attribute_option_value');

        $select = $this->read->select();
        $select->from($tableEaov);
        $select->where('store_id=' . $storeId . ' OR store_id=0');
        $select->order(array('option_id', 'store_id'));

        $attributeLabels = $this->read->fetchAll($select);

        return $attributeLabels;
    }

    /**
     * Get tax rates
     *
     * @return array
     */
    public function getTaxRates()
    {
        $tableTc = $this->resource->getTableName('tax_class');
        $tableTcc = $this->resource->getTableName('tax_calculation');
        $tableTcr = $this->resource->getTableName('tax_calculation_rate');
        $tableDcr = $this->resource->getTableName('directory_country_region');
        $tableCg = $this->resource->getTableName('customer_group');


        $select = $this->read->select();
        $select->from($tableTc)->order(array('class_id', 'tax_calculation_rate_id'));
        $select->joinleft(
            array('tc' => $tableTcc), 'tc.product_tax_class_id = ' . $tableTc . '.class_id', 'tc.tax_calculation_rate_id'
        );
        $select->joinleft(
            array('tcr' => $tableTcr), 'tcr.tax_calculation_rate_id = tc.tax_calculation_rate_id', array('tcr.rate', 'tax_country_id', 'tax_region_id')
        );
        $select->joinleft(array('dcr' => $tableDcr), 'dcr.region_id=tcr.tax_region_id', 'code');
        $select->joinInner(
            array('cg' => $tableCg), 'cg.tax_class_id=tc.customer_tax_class_id AND cg.customer_group_code="NOT LOGGED IN"'
        );

        $taxRates = $this->read->fetchAll($select);

        return $taxRates;
    }

    /**
     * Get reviews and rates
     *
     * @return array
     */
    public function getReviewsAndRates()
    {
        $tableR = $this->resource->getTableName('review');
        $tableRs = $this->resource->getTableName('review_store');
        $tableRov = $this->resource->getTableName('rating_option_vote');

        $sqlByStoreId = $this->read->select()->distinct('review_id');
        $sqlByStoreId->from(
            array('r' => $tableR), array('COUNT(DISTINCT r.review_id) AS count', 'entity_pk_value')
        );
        $sqlByStoreId->joinleft(array('rs' => $tableRs), 'rs.review_id=r.review_id', 'rs.store_id');
        $sqlByStoreId->joinleft(
            array('rov' => $tableRov), 'rov.review_id=r.review_id', 'AVG(rov.percent) AS score'
        );
        $sqlByStoreId->where('status_id=1 and entity_id=1');
        $sqlByStoreId->group(array('r.entity_pk_value', 'rs.store_id'));

        $sqlAllStoreId = $this->read->select();
        $sqlAllStoreId->from(
            array('r' => $tableR), array('COUNT(DISTINCT r.review_id) AS count', 'entity_pk_value', '(SELECT 0) AS store_id')
        );
        $sqlAllStoreId->joinleft(array('rs' => $tableRs), 'rs.review_id=r.review_id', array());
        $sqlAllStoreId->joinleft(
            array('rov' => $tableRov), 'rov.review_id=r.review_id', 'AVG(rov.percent) AS score'
        );
        $sqlAllStoreId->where('status_id=1 and entity_id=1');
        $sqlAllStoreId->group(array('r.entity_pk_value'));

        $select = $this->read->select()->union(array($sqlByStoreId, $sqlAllStoreId));
        $select->order(array('entity_pk_value', 'store_id'));

        $reviewsAndRates = $this->read->fetchAll($select);

        return $reviewsAndRates;
    }

    /**
     * Get media gallery
     *
     * @param string $storeId
     * @return array
     */
    public function getMediaGallery($storeId)
    {
        $tableCpemg = $this->resource->getTableName('catalog_product_entity_media_gallery');
        $tableCpemgv = $this->resource->getTableName('catalog_product_entity_media_gallery_value');

        $select = $this->read->select(array('DISTINCT value'));
        $select->from($tableCpemg);
        $select->joinleft(
            array('cpemgv' => $tableCpemgv), 'cpemgv.value_id = ' . $tableCpemg . '.value_id', array('cpemgv.position', 'cpemgv.disabled')
        );
        $select->where("value<>TRIM('') AND (store_id=" . $storeId . ' OR store_id=0)');
        $select->order(array('position', 'value_id'));

        $mediaGallery = $this->read->fetchAll($select);

        return $mediaGallery;
    }

    /**
     * Get configurable products from a store id
     *
     * @param string $storeId
     * @param array $attributes
     * @param boolean $joinType
     * @return Wyomind_Datafeedmanager_Model_Product_Collection
     */
    public function getConfigurableProducts($storeId, $attributes, $joinType)
    {
        $tableCpsl = $this->resource->getTableName('catalog_product_super_link');
        $tableCsi = $this->resource->getTableName('cataloginventory_stock_item');
        $tableEur = $this->resource->getTableName('enterprise_url_rewrite');
        $tableCur = $this->resource->getTableName('core_url_rewrite');
        $tableCcp = $this->resource->getTableName('catalog_category_product');
        $tableCcpi = $this->resource->getTableName('catalog_category_product_index');

        $collection = Mage::getModel('datafeedmanager/product_collection')->getCollection()->addStoreFilter($storeId);
        if (Mage::getStoreConfig('datafeedmanager/system/disabled')) {
            $collection->addFieldToFilter('status', array('gteq' => 1));
        } else {
            $collection->addFieldToFilter('status', 1);
        }

        $collection->addAttributeToFilter('type_id', array('in' => 'configurable'));
        $collection->addAttributeToFilter('visibility', array('nin' => 1));
        $collection->addAttributeToSelect($attributes, $joinType);
        $collection->getSelect()->joinLeft(
            $tableCpsl . ' AS cpsl', 'cpsl.parent_id=e.entity_id ', array('child_ids' => 'GROUP_CONCAT( DISTINCT cpsl.product_id)')
        );

        $collection->getSelect()->joinLeft(
            $tableCsi . ' AS stock', 'stock.product_id=e.entity_id', array('qty' => 'qty',
                'is_in_stock' => 'is_in_stock',
                'manage_stock' => 'manage_stock',
                'use_config_manage_stock' => 'use_config_manage_stock',
                'use_config_backorders' => 'use_config_backorders',
                'min_qty' => 'min_qty',
                'min_sale_qty' => 'min_sale_qty'
            )
        );

        if (version_compare(Mage::getVersion(), '1.13.0', '>=')) {
            $collection->getSelect()->joinLeft(
                $tableEur . ' AS url', 'url.value_id=IF(at_url_key.value_id,at_url_key.value_id,at_url_key_default.value_id) '
                . $this->notLike . ' AND is_system=1 ', array('request_path' => $this->concat . '(DISTINCT url.request_path)')
            );
        } else {
            $collection->getSelect()->joinLeft(
                $tableCur . ' AS url', 'url.product_id=e.entity_id ' . $this->notLike . ' AND is_system=1 AND ' . $this->options
                . ' AND url.store_id=' . $storeId, array('request_path' => $this->concat . '(DISTINCT request_path)')
            );
        }

        $collection->getSelect()->joinLeft($tableCcp . ' AS categories', 'categories.product_id=e.entity_id');
        $collection->getSelect()->joinLeft(
            $tableCcpi . ' AS categories_index', 'categories_index.category_id=categories.category_id '
            . 'AND categories_index.product_id=categories.product_id '
            . 'AND categories_index.store_id=' . $storeId, array('categories_ids' => 'GROUP_CONCAT( DISTINCT categories_index.category_id)')
        );
        $collection->getSelect()->group('e.entity_id');

        return $collection;
    }

    /**
     * Get quantity of configurable products
     *
     * @param string $storeId
     * @return object
     */
    public function getConfigurableQuantity($storeId)
    {
        $tableCpsl = $this->resource->getTableName('catalog_product_super_link');
        $tableCsi = $this->resource->getTableName('cataloginventory_stock_item');

        $collection = Mage::getModel('datafeedmanager/product_collection')->getCollection()->addStoreFilter($storeId);
        if (Mage::getStoreConfig('datafeedmanager/system/disabled')) {
            $collection->addFieldToFilter('status', array('gteq' => 1));
        } else {
            $collection->addFieldToFilter('status', 1);
        }
        $collection->addAttributeToFilter('type_id', array('in' => 'configurable'));
        $collection->addAttributeToFilter('visibility', array('nin' => 1));
        $collection->getSelect()->joinLeft($tableCpsl . ' AS cpsl', 'cpsl.parent_id=e.entity_id ');
        $collection->getSelect()->joinLeft(
            $tableCsi . ' AS stock', 'stock.product_id=cpsl.product_id', array('qty' => 'SUM(stock.qty)')
        );

        return $collection;
    }

    /**
     * Get product count from collection
     *
     * @param Wyomind_Datafeedmanager_Model_Product_Collection $collection
     * @param string $distinct
     * @param array|string $group
     * @return Wyomind_Datafeedmanager_Model_Product_Collection
     */
    public function getProductCount($collection, $distinct, $group = null)
    {
        $collection->getSelect()->columns('COUNT(DISTINCT ' . $distinct . ') AS total');
        if (null !== $group) {
            $collection->getSelect()->reset(Zend_Db_Select::GROUP);
            $collection->getSelect()->group($group);
        }

        return $collection->getFirstItem()->getTotal();
    }

    /**
     * Limit product collection
     *
     * @param Wyomind_Datafeedmanager_Model_Product_Collection $collection
     * @param string $count
     * @param string $offset
     * @param array|string $group
     * @return Wyomind_Datafeedmanager_Model_Product_Collection
     */
    public function limitProductCollection($collection, $count, $offset, $group = null)
    {
        if (null !== $group) {
            $collection->getSelect()->group($group);
        }

        $collection->getSelect()->limit($count, $offset);

        return $collection;
    }

    /**
     * Get configurable prices
     *
     * @return array
     */
    public function getConfigurablePrices()
    {
        $tableCpsl = $this->resource->getTableName('catalog_product_super_link');
        $tableCpsa = $this->resource->getTableName('catalog_product_super_attribute');
        $tableCpei = $this->resource->getTableName('catalog_product_entity_int');
        $tableCpsap = $this->resource->getTableName('catalog_product_super_attribute_pricing');

        $sqlConfigPrices = $this->read->select();
        $sqlConfigPrices->from(array('cpsl' => $tableCpsl), array('parent_id', 'product_id'));
        $sqlConfigPrices->joinleft(
            array('cpsa' => $tableCpsa), 'cpsa.product_id = cpsl.parent_id', array('attribute_id')
        );
        $sqlConfigPrices->joinleft(
            array('cpei' => $tableCpei), 'cpei.entity_id = cpsl.product_id AND cpei.attribute_id = cpsa.attribute_id', array('value' => 'value')
        );
        $sqlConfigPrices->joinleft(
            array('cpsap' => $tableCpsap), 'cpsap.product_super_attribute_id = cpsa.product_super_attribute_id AND cpei.value = cpsap.value_index', array('pricing_value' => 'pricing_value', 'is_percent' => 'is_percent')
        );

        $sqlConfigPrices->order(array('cpsl.parent_id', 'cpsl.product_id'));
        $sqlConfigPrices->group(array('cpsl.parent_id', 'cpsl.product_id', 'cpsa.attribute_id'));

        $configPrices = $this->read->fetchAll($sqlConfigPrices);

        return $configPrices;
    }

    /**
     * Get products relationships
     *
     * @return array
     */
    public function getRelationship()
    {
        $tableCpsl = $this->resource->getTableName('catalog_product_super_link');
        $tableCpsa = $this->resource->getTableName('catalog_product_super_attribute');
        $tableCpsal = $this->resource->getTableName('catalog_product_super_attribute_label');

        $sqlRelationship = $this->read->select();
        $sqlRelationship->from(array('cpsl' => $tableCpsl), array('parent_id', 'product_id'));
        $sqlRelationship->joinleft(
            array('cpsa' => $tableCpsa), 'cpsa.product_id = cpsl.parent_id', array('attribute_id')
        );
        $sqlRelationship->joinleft(
            array('cpsal' => $tableCpsal), 'cpsal.product_super_attribute_id = cpsa.product_super_attribute_id', array('relationship' => "GROUP_CONCAT(DISTINCT cpsal.value SEPARATOR '>>>')")
        );
        $sqlRelationship->order(array('cpsl.parent_id', 'cpsl.product_id'));
        $sqlRelationship->group(array('cpsl.parent_id', 'cpsl.product_id'));

        $relationship = $this->read->fetchAll($sqlRelationship);

        return $relationship;
    }

    /**
     * Get grouped products from a store id
     *
     * @param string $storeId
     * @param array $attributes
     * @param boolean $joinType
     * @return Wyomind_Datafeedmanager_Model_Product_Collection
     */
    public function getGroupedProducts($storeId, $attributes, $joinType)
    {
        $tableCpl = $this->resource->getTableName('catalog_product_link');
        $tableCsi = $this->resource->getTableName('cataloginventory_stock_item');
        $tableCcpi = $this->resource->getTableName('catalog_category_product_index');
        $tableCcp = $this->resource->getTableName('catalog_category_product');

        $collection = Mage::getModel('datafeedmanager/product_collection')->getCollection()->addStoreFilter($storeId);
        if (Mage::getStoreConfig('datafeedmanager/system/disabled')) {
            $collection->addFieldToFilter('status', array('gteq' => 1));
        } else {
            $collection->addFieldToFilter('status', 1);
        }

        $collection->addAttributeToFilter('type_id', array('in' => 'grouped'));
        $collection->addAttributeToFilter('visibility', array('nin' => 1));
        $collection->addAttributeToSelect($attributes, $joinType);
        $collection->getSelect()->joinLeft(
            $tableCpl . ' AS cpl', 'cpl.product_id=e.entity_id AND cpl.link_type_id=3', array('child_ids' => 'GROUP_CONCAT( DISTINCT cpl.linked_product_id)')
        );
        $collection->getSelect()->joinLeft(
            $tableCsi . ' AS stock', 'stock.product_id=e.entity_id', array('qty' => 'qty',
                'is_in_stock' => 'is_in_stock',
                'manage_stock' => 'manage_stock',
                'use_config_manage_stock' => 'use_config_manage_stock',
                'use_config_backorders' => 'use_config_backorders',
                'min_qty' => 'min_qty',
                'min_sale_qty' => 'min_sale_qty'
            )
        );

        $collection->getSelect()->joinLeft($tableCcp . ' AS categories', 'categories.product_id=e.entity_id');
        $collection->getSelect()->joinLeft(
            $tableCcpi . ' AS categories_index', 'categories_index.category_id=categories.category_id '
            . 'AND categories_index.product_id=categories.product_id '
            . 'AND categories_index.store_id=' . $storeId, array('categories_ids' => 'GROUP_CONCAT( DISTINCT categories_index.category_id)')
        );

        $collection->getSelect()->group(array('cpl.product_id'));

        return $collection;
    }

    /**
     * Get bundle products from a store id
     *
     * @param string $storeId
     * @param array $attributes
     * @param boolean $joinType
     * @return Wyomind_Datafeedmanager_Model_Product_Collection
     */
    public function getBundleProducts($storeId, $attributes, $joinType)
    {
        $tableCsi = $this->resource->getTableName('cataloginventory_stock_item');
        $tableCcp = $this->resource->getTableName('catalog_category_product');
        $tableCcpi = $this->resource->getTableName('catalog_category_product_index');
        $tableCpbs = $this->resource->getTableName('catalog_product_bundle_selection');

        $collection = Mage::getModel('datafeedmanager/product_collection')->getCollection()->addStoreFilter($storeId);
        if (Mage::getStoreConfig('datafeedmanager/system/disabled')) {
            $collection->addFieldToFilter("status", array('gteq' => 1));
        } else {
            $collection->addFieldToFilter("status", 1);
        }

        $collection->addAttributeToFilter('type_id', array('in' => 'bundle'));
        $collection->addAttributeToFilter('visibility', array('nin' => 1));
        $collection->addAttributeToSelect($attributes, $joinType);
        $collection->getSelect()->joinLeft(
            $tableCpbs . ' AS cpbs', 'cpbs.parent_product_id=e.entity_id', array('child_ids' => 'GROUP_CONCAT( DISTINCT cpbs.product_id)')
        );
        $collection->getSelect()->joinLeft(
            $tableCsi . ' AS stock', 'stock.product_id=e.entity_id', array(
                'qty' => 'qty',
                'is_in_stock' => 'is_in_stock',
                'manage_stock' => 'manage_stock',
                'use_config_manage_stock' => 'use_config_manage_stock',
                'use_config_backorders' => 'use_config_backorders',
                'min_qty' => 'min_qty',
                'min_sale_qty' => 'min_sale_qty'
            )
        );

        $collection->getSelect()->joinLeft($tableCcp . ' AS categories', 'categories.product_id=e.entity_id');
        $collection->getSelect()->joinLeft(
            $tableCcpi . ' AS categories_index', 'categories_index.category_id=categories.category_id '
            . 'AND categories_index.product_id=categories.product_id ' . 'AND categories_index.store_id=' . $storeId, array('categories_ids' => 'GROUP_CONCAT( DISTINCT categories_index.category_id)')
        );

        $collection->getSelect()->group(array('e.entity_id'));

        return $collection;
    }

    /**
     * Get tier prices
     *
     * @param string $websiteId
     * @return array
     */
    public function getTierPrices($websiteId)
    {
        $tableCpetp = $this->resource->getTableName('catalog_product_entity_tier_price');
        $sqlTierPrice = $this->read->select();
        $sqlTierPrice->from(
            array('cpetp' => $tableCpetp), array('entity_id', 'all_groups', 'customer_group_id', 'value', 'qty')
        );
        $sqlTierPrice->order(array('cpetp.entity_id', 'cpetp.customer_group_id', 'cpetp.qty'));
        $sqlTierPrice->where('cpetp.website_id=' . $websiteId . ' OR cpetp.website_id=0');

        $tierPrices = $this->read->fetchAll($sqlTierPrice);

        return $tierPrices;
    }

    /**
     * Get custom options
     *
     * @return array
     */
    public function getCustomOptions()
    {
        $tableCpo = $this->resource->getTableName('catalog_product_option');
        $tableCpot = $this->resource->getTableName('catalog_product_option_title');
        $tableCpotv = $this->resource->getTableName('catalog_product_option_type_value');
        $tableCpott = $this->resource->getTableName('catalog_product_option_type_title');
        $tableCpotp = $this->resource->getTableName('catalog_product_option_type_price');

        $sqlCustomOptions = $this->read->select();
        $sqlCustomOptions->from(array('cpo' => $tableCpo), array('product_id'));
        $sqlCustomOptions->joinleft(
            array('cpot' => $tableCpot), 'cpot.option_id=cpo.option_id AND cpot.store_id=0', array('option' => 'title', 'option_id', 'store_id')
        );
        $sqlCustomOptions->joinleft(
            array('cpotv' => $tableCpotv), 'cpotv.option_id = cpo.option_id', 'sku'
        );
        $sqlCustomOptions->joinleft(
            array('cpott' => $tableCpott), 'cpott.option_type_id=cpotv.option_type_id AND cpott.store_id=cpot.store_id', 'title AS value'
        );
        $sqlCustomOptions->joinleft(
            array('cpotp' => $tableCpotp), 'cpotp.option_type_id=cpotv.option_type_id AND cpotp.store_id=cpot.store_id', array('price', 'price_type')
        );

        $select = $sqlCustomOptions->order(array('product_id', 'cpotv.sort_order ASC'));

        $customOptions = $this->read->fetchAll($select);

        return $customOptions;
    }

    public function prepareAttributesFilter($attributesFilter, $condition, $manageStock)
    {
        $filter = array();
        $a = 0;

        foreach ($attributesFilter as $attributeFilter) {
            $attributeFilter->value = Mage::helper('datafeedmanager')->execPhpScript($attributeFilter->value, null);
            if ($attributeFilter->checked) {
                if ($attributeFilter->condition == 'in' || $attributeFilter->condition == 'nin') {
                    if ($attributeFilter->code == 'qty' || $attributeFilter->code == 'is_in_stock') {
                        $array = explode(',', $attributeFilter->value);
                        $attributeFilter->value = "'" . implode($array, "','") . "'";
                    } else {
                        $attributeFilter->value = explode(',', $attributeFilter->value);
                    }
                }
                switch ($attributeFilter->code) {
                    case 'qty' :
                        if ($a > 0) {
                            $this->where .= ' ' . $attributeFilter->statement . ' ';
                        }
                        $this->where .= ' qty ';
                        $this->where .= sprintf($condition[$attributeFilter->condition], $attributeFilter->value);

                        $a++;
                        break;
                    case 'is_in_stock' :
                        if ($a > 0) {
                            $this->where .= ' ' . $attributeFilter->statement . ' ';
                        }

                        $this->where .= " (IF(";
                        // use_config_manage_stock=1 && default_manage_stock=0 
                        $this->where .= "(use_config_manage_stock=1 AND $manageStock=0)";
                        // use_config_manage_stock=0 && manage_stock=0
                        $this->where .= " OR ";
                        $this->where .= '(use_config_manage_stock=0 AND manage_stock=0)';
                        // use_config_manage_stock=1 && default_manage_stock=1 && in_stock=1
                        $this->where .= " OR ";
                        $this->where .= "(use_config_manage_stock=1 AND $manageStock=1 AND is_in_stock=1 )";
                        // use_config_manage_stock=0 && manage_stock=1 && in_stock=1
                        $this->where .= " OR ";
                        $this->where .= "(use_config_manage_stock=0 AND manage_stock=1 AND is_in_stock=1 )";
                        $this->where .= ",'1','0')";
                        $this->where .= sprintf($condition[$attributeFilter->condition], $attributeFilter->value) . ")";

                        $a++;
                        break;
                    default :
                        if (isset($attributeFilter->statement) && $attributeFilter->statement == 'AND') {
                            if (Mage::helper('datafeedmanager')->arraySize($filter)) {
                                $this->collection->addFieldToFilter($filter);
                            }
                            $filter = array();
                        }

                        if ($attributeFilter->condition == "in") {
                            $finset = true;
                            $findInSet = array();
                            foreach ($attributeFilter->value as $v) {
                                if (!is_numeric($v)) {
                                    $finset = true;
                                }
                            }
                            if ($finset) {
                                foreach ($attributeFilter->value as $v) {
                                    $findInSet[] = array(array('finset' => $v));
                                }

                                $filter[] = array('attribute' => $attributeFilter->code, $findInSet);
                            } else {
                                $filter[] = array(
                                    'attribute' => $attributeFilter->code,
                                    $attributeFilter->condition => $attributeFilter->value
                                );
                            }
                        } else {
                            $filter[] = array(
                                'attribute' => $attributeFilter->code,
                                $attributeFilter->condition => $attributeFilter->value
                            );
                        }

                        break;
                }
            }
        }
        if ($this->where) {
            $this->collection->getSelect()->where($this->where);
        }
        return $filter;
    }

    /**
     * Get weee tawes
     *
     * @param string $storeId
     * @return array
     */
    public function getWeeTaxes($websiteId)
    {


        $tableWt = $this->resource->getTableName('weee_tax');
        $tableEavAttr = $this->resource->getTableName('eav_attribute');

        $sqlWeeeTax = $this->read->select();
        $sqlWeeeTax->from(array('wt' => $tableWt));
        $sqlWeeeTax->where("website_id = $websiteId OR website_id='0'");
        $sqlWeeeTax->order("website_id ASC");
        $sqlWeeeTax->joinleft(
            array('eavattr' => $tableEavAttr), 'eavattr.attribute_id = wt.attribute_id', 'eavattr.attribute_code'
        );
        $weeeTaxes = $this->read->fetchAll($sqlWeeeTax);


        foreach ($weeeTaxes as $weeeTax) {

            if (!isset($results[$weeeTax["attribute_code"]])) {
                $results[$weeeTax["attribute_code"]] = array();
            }
            if (!isset($results[$weeeTax["attribute_code"]][$weeeTax["entity_id"]])) {
                $results[$weeeTax["attribute_code"]][$weeeTax["entity_id"]] = array();
            }
            if (!isset($results[$weeeTax["attribute_code"]][$weeeTax["entity_id"]][$weeeTax["country"]])) {
                $results[$weeeTax["attribute_code"]][$weeeTax["entity_id"]][$weeeTax["country"]] = array();
            }
            $results[$weeeTax["attribute_code"]][$weeeTax["entity_id"]][$weeeTax["country"]][$weeeTax["state"]] = $weeeTax["value"];
        }
        return $results;
    }

}
