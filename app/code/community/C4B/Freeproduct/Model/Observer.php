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
 * @version      1.1.3
 */
class C4B_Freeproduct_Model_Observer
{

    /**
     * @param $giftSku
     * @return string[]
     */
    protected static function _getSkuList($giftSku)
    {
        return array_map('trim', explode(',', $giftSku));
    }

    /**
     * Delete all free products that have been added through this module before.
     * This is done before discounts are given in on the event
     * 'sales_quote_collect_totals_before'.
     *
     * @param Varien_Event_Observer $observer
     */
    public function salesQuoteCollectTotalsBefore(Varien_Event_Observer $observer)
    {
        /** @var Mage_Sales_Model_Quote $quote */
        $quote = $observer->getEvent()->getQuote();
        /**
         * The quote store is set to current store temporarily because of a possible problem in Enterprise v1.14.2.4.
         * If the following conditions are true:
         * - Multiple stores
         * - The store is being switched from A to B
         * - the product flat index is not set as build (see table core_flag) in store A but it is in store B (or vice-versa)
         *
         * When the quote_item collection is loaded, products are assigned to it. When the collection is instantiated, either flat
         * or EAV resource is set based on availability of the index per store. Here, the current store is being used to determine
         * flat availability. The Flat/EAV resource models are not interface-compatible, once it is set it should not change
         * otherwise there will be missing methods which cause fatal errors.
         * After instantiation, the store of the quote is being set which might have different flat availability and the collection
         * model will try to use the wrong resource model which will result in fatal errors.
         *
         * @see Flat and EAV product resource models are not interface compatible
         * @see Mage_Sales_Model_Resource_Quote_Item_Collection::_assignProducts()
         * @see Mage_Catalog_Model_Resource_Product_Collection:::_construct()
         * @see Mage_Catalog_Model_Resource_Product_Collection::isEnabledFlat()
         */
        $originalStore = $quote->getStoreId();
        $quote->setStoreId(Mage::app()->getStore()->getId());

        foreach ($quote->getAllItems() as $item) {
            if ($item->getIsFreeProduct()) {
                $quote->removeItem($item->getId());
            }
        }

        $quote->setStoreId($originalStore);
    }

    /**
     * Add gifts to the cart, if the current salesrule is of simple action
     * ADD_GIFT_ACTION. The rule has been validated before the event
     * 'salesrule_validator_process' is thrown that we catch.
     *
     * @param Varien_Event_Observer $observer
     */
    public function salesruleValidatorProcess(Varien_Event_Observer $observer)
    {
        /* @var $quote Mage_Sales_Model_Quote */
        $quote = $observer->getEvent()->getQuote();
        /* @var $item Mage_Sales_Model_Quote_Item */
        $item = $observer->getEvent()->getItem();
        /* @var $rule Mage_SalesRule_Model_Rule */
        $rule = $observer->getEvent()->getRule();

        if ($rule->getSimpleAction() != C4B_Freeproduct_Model_Consts::ADD_GIFT_ACTION
            || $item->getIsFreeProduct()
            || $rule->getIsApplied())
        {
            return;
        }

        try {
            $qty = (int)$rule->getDiscountAmount();
            $skus = static::_getSkuList($rule->getGiftSku());
            foreach ($skus as $sku) {
                /** @var Mage_Sales_Model_Quote_Item $freeItem */
                $freeItem = static::_getFreeQuoteItem($rule->getId(), $sku, $quote->getStoreId(), $qty);
                $quote->addItem($freeItem);
                static::_setQuoteItemTaxPercent($freeItem);
                $freeItem->setApplyingRule($rule);
            }
            $rule->setData('is_applied', true);
        } catch (RuntimeException $e) {
            Mage::logException($e);
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
    public function adminhtmlBlockSalesruleActionsPrepareform($observer)
    {
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
            'note' => Mage::helper('freeproduct')->__('Enter the SKU of the gift that should be added to the cart. You can enter a comma separated list for multiple gifts.'),
        ));
    }

    /**
     * Check if the given free product SKU is not empty and references a valid product.
     *
     * @param Varien_Event_Observer $observer
     *
     * @throws Mage_Core_Exception
     */
    public function adminhtmlControllerSalesrulePrepareSave($observer)
    {
        $request = $observer->getRequest();
        if ($request->getParam('simple_action') == C4B_Freeproduct_Model_Consts::ADD_GIFT_ACTION) {
            $giftSku = $request->getParam('gift_sku');
            if (! static::_isValidGiftSku($giftSku)) {
                // make sure that unsaved data is not lost
                $data = $request->getPost();
                Mage::getSingleton('adminhtml/session')->setPageData($data);
                // just throw an exception, Mage_Adminhtml_Promo_QuoteController::saveAction will do the rest
                throw new Mage_Core_Exception('The free product SKU must be a valid product.');
            }
        }
    }

    /**
     * Detect free products based on buyRequest object and set it as temporary attribute to
     * the product. Relevant for reordering. See also: salesQuoteProductAddAfter()
     *
     * @param Varien_Event_Observer $observer
     */
    public function catalogProductTypePrepareFullOptions(Varien_Event_Observer $observer)
    {
        if ($observer->getBuyRequest()->getData('is_free_product')) {
            $observer->getProduct()->setIsFreeProduct(true);
        }
    }

    /**
     * Adds is_free_product attribute to quote model if set to product. Relevant for reordering.
     * See also: catalogProductTypePrepareFullOptions()
     *
     * @param Varien_Event_Observer $observer
     */
    public function salesQuoteProductAddAfter(Varien_Event_Observer $observer)
    {
        foreach ($observer->getEvent()->getItems() as $quoteItem) {
            $quoteItem->setIsFreeProduct($quoteItem->getProduct()->getIsFreeProduct());
        }
    }

    /**
     * Create a free item. It has a value of 0$ in the cart no matter what the price was
     * originally. The flag is_free_product gets saved in the buy request to read it on
     * reordering, because fieldset conversion does not work from order item to quote item.
     *
     * @param int $ruleId
     * @param string $sku
     * @param int $storeId
     * @param int $qty
     * @return bool|Mage_Sales_Model_Quote_Item
     * @throws C4B_Freeproduct_Exception_InvalidQuantity
     * @throws C4B_Freeproduct_Exception_ProductNotSalable
     * @throws C4B_Freeproduct_Exception_ProductNotFound
     */
    protected static function _getFreeQuoteItem($ruleId, $sku, $storeId, $qty)
    {
        if ($qty < 1) {
            throw new C4B_Freeproduct_Exception_InvalidQuantity(sprintf(
                'C4B_Freeproduct: Invalid Gift product qty. Rule ID: %d, Gift Qty: %d',
                $ruleId, $qty
            ));
        }

        /** @var Mage_Catalog_Model_Product $product */
        $product = Mage::getModel('catalog/product');
        $product->setStoreId($storeId);
        $product->load($product->getIdBySku($sku));

        if ($product == false) {
            throw new C4B_Freeproduct_Exception_ProductNotFound(sprintf(
                'C4B_Freeproduct: Gift product not found. Rule ID: %d, Gift SKU: %s, Store ID: %d',
                $ruleId, $sku, $storeId
            ));
        }

        Mage::getModel('cataloginventory/stock_item')->assignProduct($product);

        if ($product->isSalable() == false) {
            throw new C4B_Freeproduct_Exception_ProductNotSalable(sprintf(
                'C4B_Freeproduct: Gift product not saleable. Rule ID: %d, Gift SKU: %s, Store ID: %d',
                $ruleId, $sku, $storeId
            ));
        }

        $quoteItem = Mage::getModel('sales/quote_item')->setProduct($product);
        $quoteItem
            ->setQty($qty)
            ->setCustomPrice(0.0)
            ->setOriginalCustomPrice($product->getPrice())
            ->setIsFreeProduct(true)
            ->setWeeeTaxApplied('a:0:{}') // Set WeeTaxApplied Value by default so there are no "warnings" later on during invoice creation
            ->setStoreId($storeId);

        $quoteItem->addOption(new Varien_Object(array(
            'product' => $product,
            'code' => 'info_buyRequest',
            'value' => serialize(array('qty' => $qty, 'is_free_product' => true))
        )));
        // With the freeproduct_uniqid option, items of the same free product won't get combined.
        $quoteItem->addOption(new Varien_Object(array(
            'product' => $product,
            'code' => 'freeproduct_uniqid',
            'value' => uniqid(null, true)
        )));

        return $quoteItem;
    }

    /**
     * @param $storeId
     * @param $quoteItem
     */
    protected static function _setQuoteItemTaxPercent(Mage_Sales_Model_Quote_Item $quoteItem)
    {
        $taxCalculationModel = Mage::getSingleton('tax/calculation');
        $quote = $quoteItem->getQuote();
        /* @var $taxCalculationModel Mage_Tax_Model_Calculation */
        $request = $taxCalculationModel->getRateRequest(
            $quote->getShippingAddress(),
            $quote->getBillingAddress(),
            $quote->getCustomerTaxClassId(),
            $quoteItem->getStore()
        );
        $rate = $taxCalculationModel->getRate(
            $request->setProductClassId($quoteItem->getProduct()->getTaxClassId())
        );
        $quoteItem->setTaxPercent($rate);
    }

    /**
     * @param $giftSku
     * @return bool
     */
    protected static function _isValidGiftSku($giftSku)
    {
        if (trim($giftSku) === '') {
            return false;
        }
        $skus = self::_getSkuList($giftSku);
        foreach ($skus as $sku) {
            if (! Mage::getModel('catalog/product')->getIdBySku($sku)) {
                return false;
            }
        }
        return true;
    }
}
