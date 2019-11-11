<?php

$installer = $this;

$installer->startSetup();

$collection = Mage::getSingleton('datafeedmanager/configurations')->getCollection();

foreach ($collection as $feed) {
    $pattern = $feed->getFeedProduct();
    $search = array('$myPattern=null', '$myPattern= null', '$myPattern =null', '$myPattern = null');
    $feed->setFeedProduct(str_replace($search, '$this->skip()', $pattern));
}

$collection->save();

$installer->endSetup();