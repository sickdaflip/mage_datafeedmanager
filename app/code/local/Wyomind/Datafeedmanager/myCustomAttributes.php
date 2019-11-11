<?php
/**
 * Copyright © 2018 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */
/* ---------------------------------------------------------------------------------------------------------- */
/* FOR DEVELOPERS ONLY                                                                                        */
/* ---------------------------------------------------------------------------------------------------------- */
/* ---------------------------------------------------------------------------------------------------------- */
/* * ************ DO NOT CHANGE THESE LINES **************                                                    */
/* ---------------------------------------------------------------------------------------------------------- */
class Wyomind_Datafeedmanager_Model_MyCustomAttributes extends Wyomind_Datafeedmanager_Model_Configurations
{
    public function __construct()
    {
        $this->_attributes = Mage::getModel('datafeedmanager/attributes')->getCollection();
    }

    /* --------------------------------------------------------------------------------------------------------- */
    /* this method retrieves the available custom attributes into the library                                    */
    /* --------------------------------------------------------------------------------------------------------- */

    public function _getAll()
    {
        $attr = array();
        foreach ($this->_attributes as $attribute) {
            $attr['Custom Attributes'][] = $attribute->getAttributeName();
        }
        
        return $attr;
    }

    /* ---------------------------------------------------------------------------------------------------------- */
    /* this method transforms the custom attributes to a computed value                                           */
    /* ---------------------------------------------------------------------------------------------------------- */

    public function _eval($product, $exp, $self, $model)
    {
        try {
            $outputHelper = Mage::helper('datafeedmanager');
            foreach ($this->_attributes as $attribute) {
                if ($exp['pattern'] == "{" . $attribute->getAttributeName() . "}") {
                    return $outputHelper->execPhp(
                        $attribute->getAttributeScript(), $product, $self
                    );
                }
            }
        } catch (Exception $e) {
            return $e->getMessage();
        }
        
        return $self;
    }
}