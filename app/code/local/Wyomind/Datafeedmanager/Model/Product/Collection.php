<?php
/**
 * Copyright © 2018 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */
class Wyomind_Datafeedmanager_Model_Product_Collection extends Mage_Catalog_Model_Resource_Product_Collection
{
    public function isEnabledFlat()
    {       
        return false;
    }
    
    public function getCollection()
    {
        return $this;
    }
}
