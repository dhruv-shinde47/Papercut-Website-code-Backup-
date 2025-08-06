<?php

namespace Gateway\Maktapp\Model;

use Gateway\Maktapp\Helper\Data as DataHelper;
use Gateway\Maktapp\Controller\Standard;

class Maktapp extends \Magento\Payment\Model\Method\AbstractMethod
{
    const CODE = 'maktapp';
    protected $_code = self::CODE;
    protected $_isGateway = true;
    protected $_isOffline = true;
    protected $_canRefund = true;
    protected $_canCapture = true;
    protected $_canAuthorize = true;
    protected $_canRefundInvoicePartial = true;
    protected $helper;
    protected $_minAmount = null;
    protected $_maxAmount = null;
    protected $_supportedCurrencyCodes = array('INR');
    protected $_formBlockType = 'Gateway\Maktapp\Block\Form\Maktapp';
    protected $_infoBlockType = 'Gateway\Maktapp\Block\Info\Maktapp';
    protected $urlBuilder;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Gateway\Maktapp\Helper\Data $helper,
        \Magento\Sales\Model\Order $order,
        \Magento\Sales\Model\Order\CreditmemoFactory $creditmemoFactory,
        \Magento\Sales\Model\Order\Invoice $invoice,
        \Magento\Sales\Model\Service\CreditmemoService $creditmemoService,
        \Magento\Framework\App\Request\Http $request
      

    ) {
        $this->helper = $helper;
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger
        );

        $this->_minAmount = "0.100";
        $this->_maxAmount = "1000000";
        $this->urlBuilder = $urlBuilder;
        $this->order = $order;
        $this->creditmemoFactory = $creditmemoFactory;
        $this->creditmemoService = $creditmemoService;
        $this->invoice = $invoice;
        $this->request = $request;
       
    }

    public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount){
        $mid = $this->getConfigData("MID");
        $mode = $this->getConfigData('debug');
        if ($mode == 1) {
            $url = 'https://maktapp.credit/v3/AddTransaction';
         }
        else {
            $url = 'https://maktapp.credit/v3/AddTransaction';
         }


        $order_id = $this->request->getParam('order_id');
        $transactionId = $payment->getParentTransactionId();
;
        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => "{\"MaktappRefID\":\"$transactionId\",\"orderId\":\"$order_id\",\"IBAN\":\"sample string 3\",\"AccountName\":\"Sarah Khaled\",\"Amount\":\"$amount\",\"MerchantID\":\"$mid\"}",
          CURLOPT_HTTPHEADER => array(
            "content-type: application/json"
        ),
    ));

$response = curl_exec($curl);
$err = curl_error($curl);

curl_close($curl);

if ($err) {
    $this->debugData(['transaction_id' => $transactionId, 'exception' => $err->getMessage()]);
    $this->_logger->error(__('Payment refunding error.'));
} else {
  echo $response;
}

        //echo $mid;exit;
        
        $payment
            ->setTransactionId($transactionId . '-' . \Magento\Sales\Model\Order\Payment\Transaction::TYPE_REFUND)
            ->setParentTransactionId($transactionId)
            ->setIsTransactionClosed(1)
            ->setShouldCloseParentTransaction(1);
        return $this;
        //exit;
    }


    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        if ($quote && (
                $quote->getBaseGrandTotal() < $this->_minAmount
                || ($this->_maxAmount && $quote->getBaseGrandTotal() > $this->_maxAmount))
        ) {
            return false;
        }

        return parent::isAvailable($quote);
    }

    public function canUseForCurrency($currencyCode)
    {
        /*f (!in_array($currencyCode, $this->_supportedCurrencyCodes)) {
            return false;
        }*/
        return true;
    }

	public function buildMaktappRequest($order)
    {

		$billing_address = $order->getBillingAddress();
        $params = array('MEID' => "",//$this->getConfigData("MID"),
						'UName' => "",//$this->getConfigData("merchant_username"),
                        'CurrencyCode' => $order->getOrderCurrencyCode(),
						'ItemPrice1' => round($order->getGrandTotal(), 3),
                        'CstFName' => $billing_address->getFirstName(),
						'CstLName' => $billing_address->getLastname(),
                        'CstEmail' => $order->getCustomerEmail(),
						'CstMobile' => $billing_address->getTelephone(),
                        'ItemName1' => 'Order '.$order->getRealOrderId(),
						'ItemQty1' => 1, 								
						'OrdID' => $order->getRealOrderId(), 			
    					'orderId' => $order->getRealOrderId(), 
    					//'token'=> 'E4B73FEE-F492-4607-A38D-852B0EBC91C9',
                        'token'=> $this->getConfigData('merchant_key'),
    					'customerEmail' =>  $order->getCustomerEmail(),
                       // 'ReturnURL' => $this->urlBuilder->getUrl('maktapp/Standard/Response', ['_secure' => false]));
                        "successURl" => $this->getConfigData('success_url'),
                        "failureURl" => $this->getConfigData('failur_url'));
        
		$str = 'X_MerchantID'.$params['MEID'].'X_UserName'.$params['UName'].'X_ReferenceID'.$params['OrdID'].'X_CurrencyCode'.$params['CurrencyCode'].'X_Total'.$params['ItemPrice1'].'';
		$hashstr = hash_hmac('sha256', $str, $this->getConfigData("merchant_key"));
        
        $params['Hash'] = $hashstr;        
		
        if($this->getConfigData('debug')){
            $url = $this->helper->TAP_PAYMENT_URL_TEST;
        }else{
            $url = $this->helper->TAP_PAYMENT_URL_PROD;
        }
        
        /*****/
        
              			// 
			// Pass Curl for API Payment 
			// 
			 $url = 'https://maktapp.credit/v3/AddTransaction';
			
			// E4B73FEE-F492-4607-A38D-852B0EBC91C9
			 $data = array(//'token'=> 'E4B73FEE-F492-4607-A38D-852B0EBC91C9',
                 'token'=> $this->getConfigData('merchant_key'),
				 'amount' =>round($order->getGrandTotal(), 3),
				 'currencyCode' => 'QAR' ,
				 'orderId' => $order->getRealOrderId(),
				 'note' => ' tesst payment' ,
				 'lang' => 'en' ,
				 'customerEmail' =>  $order->getCustomerEmail(),
				 'customerCountry' => 'qatar',
                 "successURl" => $this->getConfigData('success_url'),
                 "failureURl" => $this->getConfigData('failur_url')


             );
			$result=$this->curl_post($url, $data );
            $url = substr($result,11,strlen($result)-13);
        
        /*****/
        $maktapp_args_array = array();
		foreach($params as $key => $value){
			$maktapp_args_array[] = "<input type='hidden' name='$key' value='$value'/>";
		}
        return '<form action="'.$url.'" method="post" name="maktapp" id="maktapp">
  				'. implode('', $maktapp_args_array) . '
				<p style="text-align:center"><br /><br /><a style="text-decoration: none;text-align:center" href="javascript:void(0);" onclick="encodeTxnRequest()">
				<span>You will be redirected to Maktapp. please click here if you are not redirected within 30 seconds</span>
				</a></p>
				<script type="text/javascript">
					document.maktapp.submit();
					function encodeTxnRequest()
					{
						document.maktapp.submit();
					}
				</script>
				</form>';
    }

    	function curl_post($url, array $post = NULL, array $options = array()) 
		{
			$defaults = array(
				 CURLOPT_POST => 1,
				 CURLOPT_HEADER => 0,
				 CURLOPT_URL => $url,
				 CURLOPT_FRESH_CONNECT => 1,
				 CURLOPT_RETURNTRANSFER => 1,
				 CURLOPT_FORBID_REUSE => 1,
				 CURLOPT_TIMEOUT => 500,
				 CURLOPT_POSTFIELDS => http_build_query($post)
			);
			$ch = curl_init();
			curl_setopt_array($ch, ($options + $defaults));
			if( ! $result = curl_exec($ch))
			{
				trigger_error(curl_error($ch));
			}
			curl_close($ch);
			return $result; 

		}
		
		public function buildMaktappRequestold($order)
    {
		$billing_address = $order->getBillingAddress();
        $params = array('MEID' => "",//$this->getConfigData("MID"),
						'UName' => "",//$this->getConfigData("merchant_username"),
                        'CurrencyCode' => $order->getOrderCurrencyCode(),
						'ItemPrice1' => round($order->getGrandTotal(), 3),
                        'CstFName' => $billing_address->getFirstName(),
						'CstLName' => $billing_address->getLastname(),
                        'CstEmail' => $order->getCustomerEmail(),
						'CstMobile' => $billing_address->getTelephone(),
                        'ItemName1' => 'Order '.$order->getRealOrderId(),
						'ItemQty1' => 1, 								
						'OrdID' => $order->getRealOrderId(), 						
                        //'ReturnURL' => $this->urlBuilder->getUrl('maktapp/Standard/Response', ['_secure' => true]));
                       "successURl" => $this->getConfigData('success_url'),
                       "failureURl" => $this->getConfigData('failur_url'));
        
		$str = 'X_MerchantID'.$params['MEID'].'X_UserName'.$params['UName'].'X_ReferenceID'.$params['OrdID'].'X_CurrencyCode'.$params['CurrencyCode'].'X_Total'.$params['ItemPrice1'].'';
		$hashstr = hash_hmac('sha256', $str, $this->getConfigData("merchant_key"));
        
        $params['Hash'] = $hashstr;        
		
        if($this->getConfigData('debug')){
            $url = $this->helper->TAP_PAYMENT_URL_TEST."?";
        }else{
            $url = $this->helper->TAP_PAYMENT_URL_PROD."?";
        }
        $urlparam = "";
		foreach($params as $key => $val){
			$urlparam = $urlparam.$key."=".$val."&";
		}
        $url = $url .'';// $urlparam;
        return $url;
    }

    public function getRedirectUrl()
    {
        if($this->getConfigData('debug')){
            $url = $this->helper->TAP_PAYMENT_URL_TEST;
        }else{
            $url = $this->helper->TAP_PAYMENT_URL_PROD;
        }
        return $url;
    }


    public function getReturnUrl()
    {
        
    }

    public function getCancelUrl()
    {
        
    }
	
    
	public function validateResponse($returnParams) 
	{
		//$orderId	=	$_REQUEST['trackid'];
        $orderId	=	$_REQUEST['orderId'];
        //$ref = $_REQUEST['ref'];
        $ref = $_REQUEST['transid'];
        $order = $this->getOrder();
        //$order = Mage::getModel('sales/order')->loadByIncrementId($orderId);
        //var_dump($order);exit;
        //var_dump($orderId);exit;
        $key		=	$this->getConfigData("MID");
		$salt		=	$this->getConfigData("merchant_key");
		$RefID		=	$_REQUEST['transid'];
		$str 		= 	'x_account_id'.$key.'x_ref'.$RefID.'x_resultSUCCESSx_referenceid'.$orderId.'';
		$HashString = 	hash_hmac('sha256', $str, $salt);
	/*	$responseHashString	=	$_REQUEST['hash'];
		if($HashString == $responseHashString)
		{
			return true;
		}
		else
		{
			return false;
		}*/
    }
}
