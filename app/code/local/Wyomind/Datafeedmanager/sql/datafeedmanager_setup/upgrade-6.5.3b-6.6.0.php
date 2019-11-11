<?php
$installer = $this;

$installer->startSetup();

$installer->getConnection()->addColumn(
    $installer->getTable('datafeedmanager_configurations'), 'datafeedmanager_attribute_sets', array(
        'type'      => Varien_Db_Ddl_Table::TYPE_TEXT,
        'length'    => 150,
        'nullable'  => true,
        'default'   => '*',
        'comment'   => 'Attribute sets'
    )
);

$installer->endSetup();
