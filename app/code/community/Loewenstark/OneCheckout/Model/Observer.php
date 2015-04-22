<?php 

/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Loewenstark Magento License (LML 1.0).
 * It is  available through the world-wide-web at this URL:
 * http://www.loewenstark.de/licenses/lml-1.0.html
 * If you are unable to obtain it through the world-wide-web, please send an 
 * email to license@loewenstark.de so we can send you a copy immediately.
 *
 * @category   Loewenstark
 * @package    Loewenstark_OneCheckout
 * @copyright  Copyright (c) 2012 Ulrich Abelmann
 * @copyright  Copyright (c) 2012 wwg.l�wenstark im Internet GmbH
 * @license    http://www.loewenstark.de/licenses/lml-1.0.html  Loewenstark Magento License (LML 1.0)
 */

class Loewenstark_OneCheckout_Model_Observer extends Mage_Core_Model_Abstract {
	
	public function redirect($observer) {
		if (Mage::helper("onecheckout")->isActive()) {
			$response = Mage::app()->getResponse();
			$url = Mage::helper("onecheckout/checkout_url")->getCheckoutUrl();
			$response->setRedirect($url);
		}
	}
	
	/* helpful if the checkout was rewritten 
	   e.g. by mxperts noregion
	*/
	public function setCheckoutMethods() {
		if (Mage::helper("onecheckout")->isActive()) {
			$this->_setCheckoutMethods("checkout.cart.methods");
			$this->_setCheckoutMethods("checkout.cart.top_methods");
		}
	}
	
	protected function _setCheckoutMethods($parentBlockName) {
		$layout = Mage::app()->getLayout();
		
		$methodsBlock = $layout->getBlock($parentBlockName);
		
		if ($methodsBlock) {
			$methodBlock = $layout->createBlock(
				'Loewenstark_OneCheckout_Block_Cart_Method',
				'checkout.cart.methods.onecheckout',
				array('template' => 'onecheckout/cart/method.phtml', 'before' => "-")
			);
			$methodsBlock->insert($methodBlock);
			$methodsBlock->unsetChild("checkout.cart.methods.onepage");
		}		
	}
		
	public function setAddresses($observer) {
		Mage::helper("onecheckout")->setAddresses();
	}
	
	public function store() {
        $e = Mage::helper('core');
		$x = Mage::getStoreConfig("onecheckout/general/serial");
		$base = array();
    	foreach (Mage::app()->getWebsites() as $website) {
    		if($single = trim(preg_replace('/^.*?\\/\\/(.*)?\\//', '$1', $website->getConfig('web/unsecure/base_url')))){
    			$base[] = $single;
    		}
    		if($single = trim(preg_replace('/^.*?\\/\\/(.*)?\\//', '$1', $website->getConfig('web/secure/base_url')))){
    			$base[] = $single;
    		}
    	}
    	$base = array_unique($base);
		$basel = urlencode(base64_encode($e->encrypt(implode(',', $base))."xL48c#=3kspEw"));

		$ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, sprintf('http://license.loewenstark.info/onecheckout/'));
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, 's='.$x.'&b='.$basel.'&d='.urlencode(implode(',', $base)));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 50);
        $content = curl_exec($ch);
        
        if ($content != "") {
			$groups = array(
				'general'=>array(
					'fields'=>array(
						'content'=>array(
							'value'=>$content
						),
					)
				)
			);
			
			Mage::getModel('adminhtml/config_data')
					->setSection('onecheckout')
					->setGroups($groups)
					->save();
			
			Mage::getConfig()->reinit();
			Mage::app()->reinitStores();   
		}     
	}
	
	public function getCheckoutMethods() {
		if (Mage::getModel("customer/session")->getOneCheckoutKey() > 1 ||
			Mage::getModel("customer/session")->getOneCheckoutVal() > 1) {
			$t = Mage::helper("onecheckout")->__("L invalid");
			$r = Mage::helper("onecheckout/methods")->x();
			$w = Mage::app()->getWebsite();
			if (!in_array($w->getId(), Mage::helper("onecheckout")->c($w, $r))) {
				die('{"error":"'.$t.'"}');
			}
		}
	}
}
