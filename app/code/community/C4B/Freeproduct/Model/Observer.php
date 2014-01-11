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
class C4B_Freeproduct_Model_Observer {

    /**
     * Delete all free products that have been added through this module before.
     * This is done before discounts are given in on the event 
     * 'sales_quote_collect_totals_before'.
     * 
     * @param Varien_Event_Observer $observer
     */
    public function salesQuoteCollectTotalsBefore(Varien_Event_Observer $observer) {
        self::_resetFreeItems($observer->getEvent()->getQuote());
    }

    /**
     * Add gifts to the cart, if the current salesrule is of simple action 
     * ADD_GIFT_ACTION. The rule has been validated before the event
     * 'salesrule_validator_process' is thrown that we catch.
     * 
     * @param Varien_Event_Observer $observer
     */
    public function salesruleValidatorProcess(Varien_Event_Observer $observer) {
        /* @var $quote Mage_Sales_Model_Quote */
        $quote = $observer->getEvent()->getQuote();
        /* @var $item Mage_Sales_Model_Quote_Item */
        $item = $observer->getEvent()->getItem();
        /* @var $rule Mage_SalesRule_Model_Rule */
        $rule = $observer->getEvent()->getRule();
        
    	if ($rule->getSimpleAction() == C4B_Freeproduct_Model_Consts::ADD_GIFT_ACTION &&
		    !$item->getIsFreeProduct()) {
			self::_handleGift($quote, $item, $rule);
        }
    }

    /**
     * Add a new simple action to the salesrule in the backen. In the combo-box
     * you can now select 'Add a Gift' as one possible result of the given rule
     * evaluation positive. Additionally you have to enter a sku of the gift that
     * you want to make.
     * 
     * @param Varien_Event_Observer $observer
     */
    public function adminhtmlBlockSalesruleActionsPrepareform($observer) {
    	$field = $observer->getForm()->getElement('simple_action');
        $options = $field->getValues();
        $options[] = array(
            'value' => C4B_Freeproduct_Model_Consts::ADD_GIFT_ACTION,
            'label' => Mage::helper('freeproduct')->__('Add a Gift')
        );
        $field->setValues($options);
        
        $fieldset = $observer->getForm()->getElement('action_fieldset');
        $fieldset->addField('gift_sku', 'text', array(
            'name' => 'gift_sku',
            'label' => Mage::helper('freeproduct')->__('Gift SKU'),
            'title' => Mage::helper('freeproduct')->__('Gift SKU'),
            'note' => Mage::helper('freeproduct')->__('Enter the SKU of the gift that should be added to the cart'),
        ));
    }
    
    /**
     * Make sure that a gift is only added once, create a free item and add it to the cart.
     * 
     * @param Mage_Sales_Model_Quote $quote
     * @param Mage_Sales_Model_Quote_Item $item
     * @param Mage_Sales_Model_Quote_Item $rule
     */   
    protected static function _handleGift(Mage_Sales_Model_Quote $quote,
                                          Mage_Sales_Model_Quote_Item $item,
                                          Mage_SalesRule_Model_Rule $rule) {
    	if ($rule->getIsApplied()) {
    		return;
    	}
    	
    	$qty = (integer) $rule->getDiscountAmount();
    	if ($qty) {
    		$freeItem = self::_getFreeQuoteItem($quote, $rule->getGiftSku(), $item->getStoreId(), $qty);
    		self::_addAndApply($quote, $freeItem, $rule);
    	}
    }

    /**
     * Create a free item. It has a value of 0$ in the cart no matter what the price was
     * originally.
     * 
     * @param Mage_Sales_Model_Quote $quote
     * @param string $sku
     * @param int $storeId
     * @param int $qty
     * @return Mage_Sales_Quote_Item
     */
    protected static function _getFreeQuoteItem(Mage_Sales_Model_Quote $quote, $sku, $storeId, $qty) {
        if ($qty < 1) {
            return;
        }

        $product = Mage::getModel('catalog/product')->loadByAttribute('sku', $sku);
        Mage::getModel('cataloginventory/stock_item')->assignProduct($product);
        $quoteItem = Mage::getModel('sales/quote_item')->setProduct($product);
        $quoteItem->setQuote($quote)
                ->setQty($qty)
                ->setCustomPrice(0.0)
                ->setOriginalCustomPrice($product->getPrice())
                ->setIsFreeProduct(true)
                ->setStoreId($storeId);

        return $quoteItem;
    }    
    
    /**
     * Add a free item and mark that the rule was used on this item.
     * 
     * @param Mage_Sales_Model_Quote $quote
     * @param Mage_Sales_Model_Quote_Item $item
     * @param Mage_Sales_Model_Quote_Item $rule
     */
    protected static function _addAndApply(Mage_Sales_Model_Quote $quote,
                                           Mage_Sales_Model_Quote_Item $item,
                                           Mage_SalesRule_Model_Rule $rule) {
   		$quote->addItem($item);
		$item->setApplyingRule($rule);
    	$rule->setIsApplied(true);
    }

    /**
     * Delete all free items from the cart.
     * 
     * @param Mage_Sales_Model_Quote $quote
     */
    protected static function _resetFreeItems(Mage_Sales_Model_Quote $quote) {
        foreach ($quote->getAllItems() as $item) {
            if ($item->getIsFreeProduct()) {
                $quote->removeItem($item->getId());
            }
        }
    }
}