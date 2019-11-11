<?php
/**
 * Copyright © 2018 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 * 
 * Used in creating options for Yes|No config value selection
 *
 */
class Wyomind_Datafeedmanager_Model_System_Config_Source_Urls
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array('value' => 1, 'label' => Mage::helper('datafeedmanager')->__('Product url')),
            array('value' => 2, 'label' => Mage::helper('datafeedmanager')->__('Shortest category url')),
            array('value' => 3, 'label' => Mage::helper('datafeedmanager')->__('Longest category url')),
        );
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return array(
            1 => Mage::helper('datafeedmanager')->__('Individual product urls'),
            2 => Mage::helper('datafeedmanager')->__('Shortest category urls'),
            3 => Mage::helper('datafeedmanager')->__('Longest category urls')
        );
    }
}