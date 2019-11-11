<?php
/**
 * Copyright © 2018 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */
class Wyomind_Datafeedmanager_Block_Adminhtml_Configurations_Renderer_Action extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Action
{
    public function render(Varien_Object $row)
    {
        $this->getColumn()->setActions(
            array(
                array(
                    'url' => $this->getUrl('*/configurations/edit', array('id' => $row->getFeed_id())),
                    'caption' => Mage::helper('datafeedmanager')->__('Edit')
                ),
                array(
                    'url' => $this->getUrl('*/configurations/delete', array('id' => $row->getFeed_id())),
                    'confirm' => Mage::helper('datafeedmanager')->__('Are you sure you want to delete this feed ?'),
                    'caption' => Mage::helper('datafeedmanager')->__('Delete')
                ),
                array(
                    'url' => $this->getUrl(
                        '*/configurations/sample', 
                        array('feed_id' => $row->getFeed_id(), 'limit' => 10)
                    ),
                    'caption' => Mage::helper('datafeedmanager')->__('Preview') 
                                . " (" . Mage::getStoreConfig('datafeedmanager/system/preview') . " "
                                . Mage::helper('datafeedmanager')->__('products') . ")",
                    'popup' => true
                ),
                array(
                    'url' => "javascript:datafeedmanager.generate('" 
                            . $this->getUrl('*/configurations/generate', array('feed_id' => $row->getFeed_id())) 
                            . "');",
                    'confirm' => Mage::helper('datafeedmanager')->__(
                        'Generate a data feed can take a while. Are you sure you want to generate it now ?'
                    ),
                    'caption' => Mage::helper('datafeedmanager')->__('Generate')
                ),
                array(
                    'url' => $this->getUrl('*/configurations/export', array('feed_id' => $row->getFeed_id())),
                    'caption' => Mage::helper('datafeedmanager')->__('Export')
                )
            )
        );
        
        return parent::render($row);
    }
}
