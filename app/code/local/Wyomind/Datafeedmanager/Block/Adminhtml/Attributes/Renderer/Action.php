<?php
/**
 * Copyright © 2018 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */
class Wyomind_Datafeedmanager_Block_Adminhtml_Attributes_Renderer_Action extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Action
{
    public function render(Varien_Object $row)
    {
        $this->getColumn()->setActions(
            array(
                array(
                    'url' => $this->getUrl('*/*/edit', array('id' => $row->getAttribute_id())),
                    'caption' => Mage::helper('datafeedmanager')->__('Edit')
                ),
                array(
                    'url' => $this->getUrl('*/*/delete', array('id' => $row->getAttribute_id())),
                    'confirm' => Mage::helper('datafeedmanager')->__('Are you sure to delete this attribute ?'),
                    'caption' => Mage::helper('datafeedmanager')->__('Delete')
                )
            )
        );
        
        return parent::render($row);
    }
}
