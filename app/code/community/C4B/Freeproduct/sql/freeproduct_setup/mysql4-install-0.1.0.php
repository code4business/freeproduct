<?php
/**
 * Freeproduct Module
 * 
 * This module can be used free of carge to extend a magento system. Any other
 * usage requires prior permission of the code4business Software GmbH. The module
 * comes without any kind of warranty.
 * 
 * @category     C4B
 * @package      C4B_Freeproduct
 * @author       Nikolai Krambrock <freeproduct@code4business.de>
 * @copyright    code4business Software GmbH
 * @version      0.1.0
 */

/**
 * @var $installer Mage_Sales_Model_Mysql4_Setup
 */
$installer = $this;
$installer->startSetup();
$installer->getConnection()->addColumn($installer->getTable('sales/quote_item'), 'is_free_product', "tinyint(4) NOT NULL default '0'");
$installer->getConnection()->addColumn($installer->getTable('sales/order_item'), 'is_free_product', "tinyint(4) NOT NULL default '0'");
$installer->getConnection()->addColumn($installer->getTable('salesrule'), 'gift_sku', "varchar(255) NOT NULL default ''");
$installer->endSetup();