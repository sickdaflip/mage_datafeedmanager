<?php
$installer = $this;

$installer->startSetup();

$installer->getConnection()->addColumn(
    $installer->getTable('datafeedmanager'), 'datafeedmanager_categories', array(
        'type'      => Varien_Db_Ddl_Table::TYPE_TEXT,
        'length'    => 200,
        'nullable'  => true,
        'default'   => '*'
    )
);

$installer->endSetup();