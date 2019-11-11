<?php

$installer = $this;

$installer->startSetup();

$installer->run('insert into `' . $this->getTable('datafeedmanager_configurations') . '`(`feed_id`,`feed_name`,`feed_type`,`feed_path`,`feed_status`,`feed_updated_at`,`store_id`,`feed_include_header`,`feed_header`,`feed_product`,`feed_footer`,`feed_separator`,`feed_protector`,`feed_escape`,`feed_encoding`,`feed_required_fields`,`feed_enclose_data`,`feed_clean_data`,`feed_taxonomy`,`datafeedmanager_category_filter`,`datafeedmanager_categories`,`datafeedmanager_type_ids`,`datafeedmanager_visibility`,`datafeedmanager_attribute_sets`,`datafeedmanager_attributes`,`cron_expr`,`feed_extraheader`,`feed_extrafooter`,`feed_dateformat`,`ftp_enabled`,`use_sftp`,`ftp_host`,`ftp_login`,`ftp_password`,`ftp_active`,`ftp_dir`,`ftp_ssl`,`datafeed_taxonomy`) values (null,\'Siroop\',1,\'/feeds/\',1,\'2017-12-11 10:26:50\',(SELECT store_id FROM `' . $this->getTable('core_store') . '` WHERE store_id>0 LIMIT 1),0,\'<?xml version="1.0" encoding="utf-8"?>
<rss version="2.0" xmlns:g="http://base.google.com/ns/1.0" 
xmlns:s="https://merchants.siroop.ch/">
<channel>
<title>Products for Siroop Marketplace</title>
<link>https://www.example-shop.ch</link>;
<description>This is a sample feed containing the required and recommended attributes for a variety of different products</description> \',\'<item>
<g:id>{sku}</g:id>
<g:title>{name}</g:title>
<s:long_description>{description}</s:long_description>
<s:siroop_category>{siroop_category}</s:siroop_category>
{G:IMAGE_LINK}
<g:additional_image_link/>
<!-- Availability & Price -->
<s:quantity>{qty}</s:quantity>
<g:price>{price} CHF</g:price>
<s:productvat>1</s:productvat>
<s:warranty>24</s:warranty>
<!-- Unique Product Identifiers-->
<g:gtin>{ean}</g:gtin>
<g:mpn>{mpn}</g:mpn>
<s:manufacturer_name>{manufacturer}</s:manufacturer_name>
<g:brand>{manufacturer}</g:brand>
<!-- Products Attributes -->
<s:attribute name="Zusatzinformationen">{short_description}</s:attribute>
<s:attribute name="Grösse">{size}</s:attribute>
<s:attribute name="Farbe DE">{color}</s:attribute>
<s:attribute name="Breite">{width}</s:attribute>
<s:attribute name="Höhe">{height}</s:attribute>
<s:attribute name="Tiefe">{depth}</s:attribute>
<s:attribute name="Material">{material}</s:attribute>
<s:attribute name="Volumen">{volume}</s:attribute>
<s:attribute name="Geschlecht">{gender}</s:attribute>
<s:attribute name="Lieferumfang">{delivery_contents}</s:attribute>
</item>\',\'</channel>
</rss> \',\';\',\'\',\'\',\'UTF-8\',null,1,1,null,0,\'*\',\'simple,configurable,bundle,grouped,virtual,downloadable\',\'1,2,3,4\',\'*\',\'[]\',\'{"days":["Monday","Tuesday","Wednesday","Thursday","Friday","Saturday","Sunday"],"hours":["04:00"]}\',null,null,\'{f}\',0,0,null,null,null,0,null,0,\'/lib/Wyomind/Google_Taxonomy Copie.txt\')');

$installer->endSetup();
