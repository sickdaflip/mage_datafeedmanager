<?php
$installer = $this;

$installer->startSetup();

$installer->getConnection()->addColumn(
    $installer->getTable('datafeedmanager_configurations'), 'use_sftp', array(
        'type'      => Varien_Db_Ddl_Table::TYPE_SMALLINT,
        'nullable'  => true,
        'length'    => 1,
        'default'   => 0,
        'comment'   => 'Use sftp'
    )
);

$installer->getConnection()->addColumn(
    $installer->getTable('datafeedmanager_configurations'), 'feed_taxonomy', array(
        'type'      => Varien_Db_Ddl_Table::TYPE_TEXT,
        'nullable'  => true,
        'length'    => 200,
        'default'   => null,
        'comment'   => 'Feed taxonomy'
    )
);

//BLOB/TEXT column 'datafeedmanager_attribute_sets' can't have a default value
//$installer->getConnection()->modifyColumn(
//    $installer->getTable('datafeedmanager_configurations'), 'datafeedmanager_attribute_sets', array(
//        'type'      => Varien_Db_Ddl_Table::TYPE_TEXT,
//        'length'    => 1500,
//        'default'   => '*',
//        'nullable'  => true
//    )
//);

$installer->run(
    "ALTER TABLE {$this->getTable('datafeedmanager_configurations')} "
    . "MODIFY `datafeedmanager_attribute_sets` varchar(1500) default '*';"
);

$installer->endSetup();