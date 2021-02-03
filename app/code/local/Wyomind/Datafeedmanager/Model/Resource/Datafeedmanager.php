<?php
/**
 * Copyright © 2018 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */
class Wyomind_Datafeedmanager_Model_Resource_Datafeedmanager extends Mage_Core_Model_Resource_Db_Abstract
{
    public function _construct()
    {
        // Note that the datafeedmanager_id refers to the key field in your database table.
        $this->_init('datafeedmanager/datafeedmanager', 'feed_id');
    }
    
    /**
     * Get entity_attribute_collection
     * 
     * @return array
     */
    public function getEntityAttributeCollection()
    {
        /* Attribute type ID */
        $typeId = Mage::getSingleton('eav/entity_type')->loadByCode('catalog_product')->getEntityTypeId();

        /*  Attribute list from the BDD */
        $attributes = Mage::getResourceModel('eav/entity_attribute_collection')
                            ->setEntityTypeFilter($typeId)
                            ->addSetInfo()
                            ->setFrontendInputTypeFilter(array('nin' => array('gallery', 'hidden')))
                            ->getData();

        return $attributes;
    }
    
    public function importConfiguration($template)
    {
        $resource = Mage::getSingleton('core/resource');
        $writeConnection = $resource->getConnection('core_write');
        $dfmc = $resource->getTableName('datafeedmanager_configurations');
        $sql = str_replace(array('{{datafeedmanager_configurations}}', '"'), array($dfmc, '\\"'), $template);
        // old code: $sql = str_replace(array('{{datafeedmanager_configurations}}', '"', "\\'"), array($dfmc, '\\"', "'"), $template);
        // the replacement of \' with ' has been removed because of SQL errors for the profile's escape character (\)
        try {
            $writeConnection->query($sql);
            Mage::getSingleton('adminhtml/session')->addSuccess(
                Mage::helper('datafeedmanager')->__('The template has been imported.')
            );
        } catch (Mage_Core_Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
        } catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError(
                Mage::helper('datafeedmanager')->__("The template can't be imported.<br/>" . $e->getMessage())
            );
        }
    }
}
