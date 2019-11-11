<?php
$installer = $this;

$installer->startSetup();

$installer->getConnection()->addColumn(
    $installer->getTable('datafeedmanager'), 'datafeedmanager_category_filter', array(
        'type'      => Varien_Db_Ddl_Table::TYPE_SMALLINT,
        'nullable'  => true,
        'default'   => 1,
        'length'    => 1,
        'comment'   => 'Category filter'
    )
);

$installer->endSetup();