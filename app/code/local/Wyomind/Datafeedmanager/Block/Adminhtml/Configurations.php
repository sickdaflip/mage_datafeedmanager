<?php
/**
 * Copyright © 2018 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */
class Wyomind_Datafeedmanager_Block_Adminhtml_Configurations extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {
        $this->_controller = 'adminhtml_configurations';
        $this->_blockGroup = 'datafeedmanager';
        $this->_headerText = Mage::helper('datafeedmanager')->__('Data Feed Manager');
        $this->_addButtonLabel = Mage::helper('datafeedmanager')->__('Create new template');
        
        parent::__construct();
        
        $this->_addButton(
            'import', array(
            'label' => Mage::helper('datafeedmanager')->__('Import a template'),
            'onclick' => 'document.location=\''.$this->getUrl('*/*/import').'\'',
            'class' => 'save'
            )
        );
    }
}
