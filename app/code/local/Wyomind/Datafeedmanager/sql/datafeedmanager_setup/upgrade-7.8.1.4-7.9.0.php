<?php

$installer = $this;

$installer->startSetup();

$collection = Mage::getSingleton('datafeedmanager/configurations')->getCollection();
foreach ($collection as $feed) {
    $categories = json_decode($feed->getDatafeedmanagerCategories());
    $newCategories = array();
    foreach ($categories as $categorie) {
        $ids = explode("/",$categorie->line);
        $newCategories[end($ids)] = ['c'=>$categorie->checked?"1":"0", 'm'=>$categorie->mapping];
    }
    $feed->setDatafeedmanagerCategories(json_encode($newCategories));
}
$collection->save();

$installer->getConnection()->addColumn(
    $installer->getTable('datafeedmanager_configurations'), 'datafeed_taxonomy', array(
        'type'      => Varien_Db_Ddl_Table::TYPE_TEXT,
        'length'    => 200,
        'nullable'  => true,
        'comment'   => 'Taxonomy file'
    )
);

$installer->endSetup();
