<?php

/**
 * Copyright © 2018 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */
class Wyomind_Datafeedmanager_Helper_Categories extends Mage_Core_Helper_Data
{

    public function getTree()
    {
        $collection = Mage::getModel('catalog/category')->getCollection()->addAttributeToSelect('name');
        $tree = array();
        foreach ($collection as $cat) {
            if (!isset($tree[$cat->getId()])) {
                $tree[$cat->getId()] = array("id" => $cat->getId(), "text" => $cat->getName(), "children" => array());
            } else {
                $tree[$cat->getId()]['id'] = $cat->getId();
                $tree[$cat->getId()]['text'] = $cat->getName();
            }
            if ($cat->getParentId() != 0) {
                if (isset($tree[$cat->getParentId()]['children'])) {
                    array_unshift($tree[$cat->getParentId()]['children'], $cat->getId());
                } else {
                    $tree[$cat->getParentId()]['children'] = array($cat->getId());
                }
            }
        }
        return $tree;
    }

}
