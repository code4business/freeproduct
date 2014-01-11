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
class C4B_Freeproduct_Helper_Data extends Mage_Core_Helper_Abstract {
	
	/**
	 * This logs a message in a custom log file.
	 * 
	 * @param string $msg
	 */
	public function log($msg) {
		Mage::log($msg, Zend_Log::INFO, 'freeproduct.log');
	}
}