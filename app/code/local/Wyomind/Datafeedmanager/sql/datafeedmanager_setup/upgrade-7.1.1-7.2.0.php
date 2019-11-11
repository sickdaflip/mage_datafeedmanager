<?php
$installer = $this;

$installer->startSetup();

$installer->getConnection()->addColumn(
    $installer->getTable('datafeedmanager_configurations'), 'feed_extrafooter', array(
        'type'      => Varien_Db_Ddl_Table::TYPE_TEXT,
        'nullable'  => true,
        'comment'   => 'Feed extrafooter'
    )
);

$installer->getConnection()->addColumn(
    $installer->getTable('datafeedmanager_configurations'), 'feed_dateformat', array(
        'type'      => Varien_Db_Ddl_Table::TYPE_TEXT,
        'nullable'  => false,
        'length'    => 50,
        'default'   => '{f}',
        'comment'   => 'Feed dateformat'
    )
);

$installer->endSetup();