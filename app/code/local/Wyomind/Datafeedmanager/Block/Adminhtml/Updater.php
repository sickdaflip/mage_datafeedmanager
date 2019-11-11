<?php
/**
 * Copyright © 2018 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */
class Wyomind_Datafeedmanager_Block_Adminhtml_Updater extends Mage_Adminhtml_Block_Template
{
    public function _ToHtml()
    {
        $json = array();
        $data = Mage::helper('core')->jsonDecode($this->getRequest()->getPost('data'));
        
        foreach ($data as $f) {
            $row = new Varien_Object;
            $row->setId($f[0]);
            $row->setCronExpr($f[1]);
            $status = new Wyomind_Datafeedmanager_Block_Adminhtml_Configurations_Renderer_Process();
            $json[] = array('id' => $f[0], 'content' => ($status->render($row)));
        }
        
        return (Mage::helper('core')->jsonEncode($json));
    }
}