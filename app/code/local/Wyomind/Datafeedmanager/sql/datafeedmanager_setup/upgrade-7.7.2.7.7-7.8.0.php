<?php

$installer = $this;

$installer->startSetup();

$installer->getConnection()->addColumn(
    $installer->getTable('datafeedmanager_configurations'), 'ftp_ssl', array(
        'type'      => Varien_Db_Ddl_Table::TYPE_INTEGER,
        'length'    => 1,
        'nullable'  => false,
        'default'   => '0',
        'comment'   => 'Use SSL'
    )
);

$installer->endSetup();
