<?php
/**
 * Copyright © 2018 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */
class Wyomind_Datafeedmanager_Block_Adminhtml_Configurations_Edit_Tab_Categories extends Mage_Adminhtml_Block_Widget_Form
{
    protected function _prepareForm()
    {
        $form = new Varien_Data_Form();
        $model = Mage::getModel('datafeedmanager/configurations');
        $model ->load($this->getRequest()->getParam('id'));
        
        $this->setForm($form);
        $fieldset = $form->addFieldset('datafeedmanager', array('legend'=>$this->__('Categories')));
        $this->setTemplate('datafeedmanager/categories.phtml');
        
        if (Mage::registry('datafeedmanager_data')) {
            $form->setValues(Mage::registry('datafeedmanager_data')->getData());
        }
        
        return parent::_prepareForm();
    }
    
    public function dirFiles($directory)
    {
        $dir = dir($directory); //Open Directory
        while (false !== ($file = $dir->read())) {
            //Reads Directory
            $extension = substr($file, strrpos($file, '.')); // Gets the File Extension
            if ($extension == ".txt") {
                // Extensions Allowed
                $allFiles[$file] = $file; // Store in Array
            }
        }
        $dir->close(); // Close Directory
        asort($allFiles); // Sorts the Array
        return $allFiles;
    }
    
    /**
     * Get category depth
     * 
     * @param string $categoryPath
     * @return int
     */
    public function getCategoryDepth($categoryPath)
    {
        return count(explode('/', $categoryPath)) - 1;
    }
    
    public function getJsonTree()
    {
        $treeCategories = Mage::helper('datafeedmanager/categories')->getTree();
        return json_encode($treeCategories);
    }
}
