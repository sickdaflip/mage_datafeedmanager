<?php
/**
 * Copyright © 2018 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */
class Wyomind_Datafeedmanager_Block_Adminhtml_Categories extends Mage_Adminhtml_Block_Template
{
    public function _ToHtml()
    {
        $i = 0;
        $io = new Varien_Io_File();
        $lines = '';
        $file = $this->getRequest()->getParam('file');
        $realPath = $io->getCleanPath(Mage::getBaseDir() . $file);
        
        $io->streamOpen($realPath, "r+");
        while (false !== ($line = $io->streamRead())) {
            if (stripos($line, $this->getRequest()->getParam('s')) !== false) {
                $lines .= $line;
            }
        }
        
        return $lines;
    }
}