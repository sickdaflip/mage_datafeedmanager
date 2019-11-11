<?php
/**
 * Copyright © 2018 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */
class Wyomind_Datafeedmanager_Block_Datafeedmanager extends Mage_Core_Block_Template
{
    public function getDatafeedmanager()
    {
        if (!$this->hasData('datafeedmanager')) {
            $this->setData('datafeedmanager', Mage::registry('datafeedmanager'));
        }
        
        return $this->getData('datafeedmanager');
    }
}
