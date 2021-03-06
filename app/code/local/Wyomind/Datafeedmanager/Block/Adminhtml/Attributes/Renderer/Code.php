<?php
/**
 * Copyright © 2018 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */
class Wyomind_Datafeedmanager_Block_Adminhtml_Attributes_Renderer_Code extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row)
    {
        return "<span style='font-family:monospace;font-weight:bold;color:#FF1493;'>{" 
                . $row->getAttributeName() . "}</span>";
    }
}
