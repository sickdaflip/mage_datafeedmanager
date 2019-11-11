<?php
/**
 * Copyright © 2018 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */
class Wyomind_Datafeedmanager_Block_Adminhtml_Sample extends Mage_Adminhtml_Block_Template
{
    public function _ToHtml()
    {
        $id = $this->getRequest()->getParam('feed_id');

        $datafeedmanager = Mage::getModel('datafeedmanager/configurations');
        $datafeedmanager->setId($id);
        $datafeedmanager->limit = Mage::getStoreConfig('datafeedmanager/system/preview');
        $datafeedmanager->display = true;
        $datafeedmanager->load($id);
        
        try {
            $content = $datafeedmanager->generateFile();
            
            return $content;
        } catch (Mage_Core_Exception $e) {
            return $e->getMessage();
        } catch (Exception $e) {
            return Mage::helper('datafeedmanager')->__('Unable to generate the data feed.');
        }
    }
}