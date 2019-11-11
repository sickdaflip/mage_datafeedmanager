<?php
        
$installer = $this;

$installer->startSetup();

$collection = Mage::getSingleton('datafeedmanager/configurations')->getCollection();
foreach ($collection as $feed) {
    $pattern = $feed->getFeedProduct();
    $pattern = str_replace('$this->_indexPhp', '$this->indexPhp', $pattern);
    $pattern = str_replace('$this->_limit', '$this->limit', $pattern);
    $pattern = str_replace('$this->_display', '$this->display', $pattern);
    $pattern = str_replace('$this->_rates', '$this->rates', $pattern);
    $pattern = str_replace('$this->_chartset', '$this->charset', $pattern);
    $pattern = str_replace('$this->_sqlSize', '$this->sqlSize', $pattern);
    $pattern = str_replace('$this->_counter', '$this->counter', $pattern);
    $pattern = str_replace('$this->_max_attribute', '$this->maxAttribute', $pattern);
    
    $feed->setFeedProduct($pattern);
}

$collection->save();

$installer->endSetup();