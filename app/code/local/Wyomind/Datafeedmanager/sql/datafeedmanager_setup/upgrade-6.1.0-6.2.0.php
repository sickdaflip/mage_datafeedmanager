<?php
$installer = $this;

$installer->startSetup();

$installer->run('RENAME TABLE ' . $this->getTable('datafeedmanager').' TO '. $this->getTable('datafeedmanager_configurations'));

$installer->getConnection()->addColumn(
    $installer->getTable('datafeedmanager_configurations'), 'feed_encoding', array(
        'type'      => Varien_Db_Ddl_Table::TYPE_TEXT,
        'length'    => 40,
        'nullable'  => false,
        'default'   => 'UTF-8',
        'comment'   => 'Feed encoding'
    )
);

$installer->getConnection()->addColumn(
    $installer->getTable('datafeedmanager_configurations'), 'feed_escape', array(
        'type'      => Varien_Db_Ddl_Table::TYPE_TEXT,
        'length'    => 3,
        'nullable'  => false,
        'comment'   => 'Feed escape'
    )
);

$installer->getConnection()->addColumn(
    $installer->getTable('datafeedmanager_configurations'), 'feed_clean_data', array(
        'type'      => Varien_Db_Ddl_Table::TYPE_SMALLINT,
        'nullable'  => false,
        'default'   => 1,
        'length'    => 1,
        'comment'   => 'Feed clean data'
    )
);

$installer->run('DROP TABLE IF EXISTS ' . $this->getTable('datafeedmanager_attributes'));
$installer->run(
    'CREATE TABLE IF NOT EXISTS `' . $this->getTable('datafeedmanager_attributes') . '` '
    . '(`attribute_id` int(11) NOT NULL auto_increment,'
    . '`attribute_name` varchar(100) NOT NULL,'
    . '`attribute_script` text,'
    . 'PRIMARY KEY (`attribute_id`)) '
    . 'ENGINE=InnoDB DEFAULT CHARSET=utf8 ;'
);

$installer->run(
    'INSERT INTO `' . $this->getTable('datafeedmanager_attributes') . '`'
    . '(`attribute_id`,`attribute_name`,`attribute_script`) VALUES (NULL,\'configurable_sizes\','
    . '\' if ($product->type_id == \'\'configurable\'\') {'
    . '$childProducts = Mage::getModel(\'\'catalog/product_type_configurable\'\')->getUsedProducts(null, $product);'
    . '$sizes = array();foreach ($childProducts as $child) $sizes[] = $child->getAttributeText(\'\'size\'\');'
    . 'return implode(\'\',\'\', $sizes);}\');'
);

$installer->run('DROP TABLE IF EXISTS ' . $this->getTable('datafeedmanager_options'));
$installer->run(
    'CREATE TABLE IF NOT EXISTS `' . $this->getTable('datafeedmanager_options') . '` '
    . '(`option_id` int(11) NOT NULL auto_increment,'
    . '`option_name` varchar(100) NOT NULL,'
    . '`option_script` text,'
    . '`option_param` int(1),'
    . 'PRIMARY KEY (`option_id`)) '
    . 'ENGINE=InnoDB DEFAULT CHARSET=utf8 ;'
);

$installer->run(
    'INSERT INTO `' . $this->getTable('datafeedmanager_options') . '` '
    . '(`option_id`,`option_name`,`option_script`,`option_param`) VALUES '
    . '(NULL,\'number_format\',\'$self=number_format($self,$param[1],$param[2],$param[3]);\',3),'
    . '(NULL,\'str_pad_left\',\'$self=str_pad($self,$param[1],$param[2],STR_PAD_LEFT);\',2);'
);

$installer->endSetup();