<?php

namespace Gateway\Maktapp\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Sales\Model\Order;
use Magento\Framework\App\Helper\AbstractHelper;

class Data extends AbstractHelper
{
    protected $session;
    public $TAP_PAYMENT_URL_PROD = "https://maktapp.credit/v3/AddTransaction";

    public $TAP_PAYMENT_URL_TEST = "https://maktapp.credit/v3/AddTransactions";
 
    public function __construct(Context $context, \Magento\Checkout\Model\Session $session) {
        $this->session = $session;
        parent::__construct($context);
    }

    public function cancelCurrentOrder($comment) {
        $order = $this->session->getLastRealOrder();
        if ($order->getId() && $order->getState() != Order::STATE_CANCELED) {
            $order->registerCancellation($comment)->save();
            return true;
        }
        return false;
    }
	
	public function validateResponse($returnParams) 
	{
		$orderId	=	$_REQUEST['orderId'];
		
        $key		=	$this->getConfigData("MID");// '1014';//$this->getConfigData("MID");
		$salt		=	$this->getConfigData("merchant_key");//'1tap7';//$this->getConfigData("merchant_key");
		$RefID=$_REQUEST['transid'];
		$str = 'x_account_id'.$key.'x_ref'.$RefID.'x_resultSUCCESSx_referenceid'.$orderId.'';
		$str = 'orderId'.$orderId;

		return true;
    }

	
    public function restoreQuote() {
        return $this->session->restoreQuote();
    }

    public function getUrl($route, $params = []) {
        return $this->_getUrl($route, $params);
    }
}
