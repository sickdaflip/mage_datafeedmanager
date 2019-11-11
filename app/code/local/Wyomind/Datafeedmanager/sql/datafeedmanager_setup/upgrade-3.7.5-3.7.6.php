<?php
$installer = $this;

$installer->startSetup();

$installer->getConnection()->addColumn(
    $installer->getTable('datafeedmanager'), 'cron_expr', array(
        'type'      => Varien_Db_Ddl_Table::TYPE_TEXT,
        'length'    => 200,
        'nullable'  => false,
        'default'   => '* * * * *'
    )
);

$installer->getConnection()->modifyColumn(
    $installer->getTable('datafeedmanager'), 'datafeedmanager_categories', array(
        'type'      => Varien_Db_Ddl_Table::TYPE_TEXT,
        'nullable'  => true
    )
);

$installer->endSetup();