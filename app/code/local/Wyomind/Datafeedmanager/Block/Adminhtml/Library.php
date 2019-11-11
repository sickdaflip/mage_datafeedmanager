<?php
/**
 * Copyright © 2018 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */
class Wyomind_Datafeedmanager_Block_Adminhtml_Library extends Mage_Adminhtml_Block_Template
{
    public function _ToHtml()
    {
        $attributeList = Mage::helper('datafeedmanager')->getOrderedAttributeList();
        
        $tabOutput = '<div id="blackbox-library"><ul><h3>Attribute groups</h3>';
        $contentOutput = '<table >';
        $tabOutput .= '<li><a href="#attributes">Base Attributes</a></li>';
        $contentOutput .= '<tr><td><a name="attributes"></a><b>Base Attributes</b></td></tr>';
        
        foreach ($attributeList as $attribute) {
            if (!empty($attribute['frontend_label'])) {
                $contentOutput.= '<tr><td>' . $attribute['frontend_label'] . '</td>'
                        . '<td><span class="pink">{' . $attribute['attribute_code'] . '}</span></td></tr>';
            }
        }

        foreach ($attributeList as $attribute) {
            if (!empty($attribute['attribute_code']) && empty($attribute['frontend_label'])) {
                $contentOutput.= '<tr><td>' . $attribute['frontend_label'] . '</td>'
                        . '<td><span class="pink">{' . $attribute['attribute_code'] . '}</span></td></tr>';
            }
        }

        $class = new Wyomind_Datafeedmanager_Model_Configurations;
        $myCustomAttributes = new Wyomind_Datafeedmanager_Model_MyCustomAttributes;

        foreach ($myCustomAttributes->_getAll() as $group => $attributes) {
            $tabOutput .= '<li><a href="#' . $group . '"> ' . $group . '</a></li>';
            $contentOutput .= '<tr><td><a name="' . $group . '"></a><b>' . $group . '</b></td></tr>';
            foreach ($attributes as $attr) {
                $contentOutput.= '<tr><td><span class="pink">{' . $attr . '}</span></td></tr>';
            }
        }

        $tabOutput .= ' <li><a href="https://www.wyomind.com/data-feed-manager-magento.html?section=documentation#doc_1bqvbrm842k"'
            . ' class="external_link" target="_blank" >Attribute Options</a></li>';
        $tabOutput .= ' <li><a href="https://www.wyomind.com/data-feed-manager-magento.html?section=documentation#doc_1bqvbrm8434"'
                            . ' class="external_link" target="_blank" >PHP API</a></li>';
        $contentOutput .= '</table></div>';
        $tabOutput .= '</ul>';
        
        return $tabOutput . $contentOutput;
    }
}