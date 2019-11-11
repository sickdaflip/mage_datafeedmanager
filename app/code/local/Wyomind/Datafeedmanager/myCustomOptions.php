<?php

/**
 * Copyright © 2018 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */
class Wyomind_Datafeedmanager_Model_MyCustomOptions extends Wyomind_Datafeedmanager_Model_Configurations
{

    public function __construct()
    {
        $this->_options = Mage::getModel('datafeedmanager/options')->getCollection();
    }

    /* --------------------------------------------------------------------------------------------------------- */
    /* this method retrieves the available custom attributes into the library                                    */
    /* --------------------------------------------------------------------------------------------------------- */

    public function _getAll()
    {
        $attr = array();
        foreach ($this->_options as $option) {
            $attr['Custom Options'][] = $option->getOptionName();
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
            $found = false;

            foreach ($this->_options as $option) {
                if ($exp['options'][$this->option] == "" . $option->getOptionName() . "") {
                    $parameters = $option->getOptionParam();
                    for ($i = 0; $i <= $parameters; $i++) {
                        $param[$i] = $exp['options'][$this->option + $i];
                    }

                    $self = $outputHelper->execPhp(
                            $option->getOptionScript(), $product, $self, $param
                    );

                    $this->skipOptions(1 + $parameters);
                    $found = true;
                }
            }

            if (!$found) {
                $self = $outputHelper->execPhp(
                        'return ' . $exp['options'][$this->option] . ';', $product, $self
                );
                $this->skipOptions(1);
            }
        } catch (Exception $e) {
            return $e->getMessage();
        }

        return $self;
    }

}
