<?php
/**
 * Copyright © 2018 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */

class Wyomind_Datafeedmanager_Helper_Data extends Mage_Core_Helper_Data
{
    public $skipProduct = false;
    protected $_profile = null;
    
    public function getOrderedAttributeList()
    {
        $attributeList = Mage::getResourceModel('datafeedmanager/datafeedmanager')->getEntityAttributeCollection();
        
        $attributeList[] = array('attribute_code' => 'qty', 'frontend_label' => 'Quantity');
        $attributeList[] = array('attribute_code' => 'is_in_stock', 'frontend_label' => 'Is in stock');
        $attributeList[] = array('attribute_code' => 'entity_id', 'frontend_label' => 'Product ID');
        $attributeList[] = array('attribute_code' => 'created_at', 'frontend_label' => 'Created at');
        $attributeList[] = array('attribute_code' => 'updated_at', 'frontend_label' => 'Updated at');
        
        usort($attributeList, array('Wyomind_Datafeedmanager_Helper_Data', 'cmp'));
        
        return $attributeList;
    }
    
    public function cmp($a, $b) 
    {
        return ($a['frontend_label'] < $b['frontend_label']) ? -1 : 1;
    }
    
    public function setProfile($model)
    {
        $this->_profile = $model;
    }
    
    /**
     * Get attribute options
     * 
     * @param string $attributeId
     * @return array
     */
    public function getAttributesOptions($attributeId)
    {
        $options = array();
        
        $attribute = Mage::getModel('catalog/resource_eav_attribute')->load($attributeId);
        $attributeOptions = $attribute->getSource()->getAllOptions();
        
        foreach ($attributeOptions as $attributeOption) {
            if ((string) $attributeOption['value'] != '') {
                $options[] = $attributeOption;
            }
        }
        
        return $options;
    }
    
    public function execPhp($code, $product, $self = null, $param = null)
    {
        return eval($code);
    }
    
    public function execPhpScript($myPattern, $product, $xml = true, $header = false)
    {
        if ($header && $xml == 1) {
            $split = preg_split("/\n/", $myPattern);
            $firstLine = $split[0];
            unset($split[0]);
            $myPattern = implode("\n", $split);
        }

        $myPattern = str_replace('<?', utf8_encode('__PHP__'), $myPattern);
        $myPattern = str_replace('?>', utf8_encode('/__PHP__'), $myPattern);
        $pattern = utf8_encode('#(__PHP__)(.*?)/\1#s');

        preg_match_all($pattern, $myPattern, $matches);

        if (isset($matches[1])) {
            foreach ($matches[0] as $key => $script) {
                try {
                    if ($xml == 1) {
                        $eval = $this->execPhp($matches[2][$key] . ';', $product);
                        $myPattern = str_replace($script, $eval, $myPattern);
                    } else {
                        $eval = $this->execPhp(str_replace("\\\"", "\"", $matches[2][$key]) . ';', $product);
                        $myPattern = str_replace(
                            $script, Mage::helper('datafeedmanager/output')->escapeStr($eval), $myPattern
                        );
                    }
                } catch (Exception $e) {
                    $myPattern = str_replace($script, $e->getMessage(), $myPattern);
                }
            }
        }
        
        if ($header && $xml == 1) {
            return $firstLine . "\n" . $myPattern;
        } else {
            return $myPattern;
        }
    }
    
    public function hasParent($product, $type = 'parent')
    {
        $parent = $this->_profile->checkReference($type, $product);
        if ($parent != $product) {
            return true;
        } else {
            return false;
        }
    }

    public function getParent($product, $type = 'parent', $force = true)
    {
        $parent = $this->_profile->checkReference($type, $product);
        
        if ($force) {
            return $parent;
        } elseif ($parent != $product) {
            return $parent;
        }
        return null;
    }
    
    public function skip($skip = true)
    {
        $this->skipProduct = $skip;
    }
    
    public function getSkip()
    {
        return $this->skipProduct;
    }
    
    public function arraySize($array)
    {
        return count($array);
    }

    public function childPrice($product)
    {
        /**
         * Must be used in price.html template as follows in combination with the {url,[variant]} variable
         * list($price,$finalPrice) = Mage::helper('datafeedmanager/data')->childPrice($_product);
         * $_product->setPrice($price);
         * $_product->setFinalPrice($finalPrice);
         */
        $price = $product->getPrice();
        $finalPrice = $product->getFinalPrice();
        $percentage = $finalPrice / $price;
        $childId = Mage::app()->getRequest()->getParam('c');

        if ($childId) {
            $attributeArrays = ($product->getTypeInstance(true)->getConfigurableAttributesAsArray($product));
            foreach ($attributeArrays as $attribute) {
                if (($optionValue = Mage::app()->getRequest()->getParam($attribute['attribute_id'])) !== FALSE) {
                    foreach ($attribute['values'] as $value) {
                        if ($optionValue == $value['value_index']) {
                            if (!$value['is_percent']) {
                                $price += $value['pricing_value'];
                                $finalPrice += $value['pricing_value'] * $percentage;
                            } else {
                                $price += $price * $value['pricing_value'] / 100;
                                $finalPrice += $price * $value['pricing_value'] / 100 * $percentage;
                            }
                            break;
                        }
                    }
                }
            }
        }

        Mage::app()->getRequest()->setParam('c', false);

        return array($price, $finalPrice);
    }
}