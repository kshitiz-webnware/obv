<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace ObvPayment\Model;


class ObvPaymentMethod extends \Magento\Payment\Model\Method\AbstractMethod
{

	protected $_isInitializeNeeded      = false;
    protected $redirect_uri;
    protected $_code = 'obvpayment';
 	protected $_canOrder = true;
	protected $_isGateway = true; 
	
	
	
	
    public function getOrderPlaceRedirectUrl() {
	   return \Magento\Framework\App\ObjectManager::getInstance()
							->get('Magento\Framework\UrlInterface')->getUrl("obvpayment/redirect");
   } 
}
