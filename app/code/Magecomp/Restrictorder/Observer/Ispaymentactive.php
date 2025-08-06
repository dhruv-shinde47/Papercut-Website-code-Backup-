<?php
 
namespace Magecomp\Restrictorder\Observer;
 
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\Request\Http;

class Ispaymentactive implements ObserverInterface
{
	  protected $objectManager;
	  protected $_helperdata;
	  public function __construct(
	  \Magento\Framework\ObjectManagerInterface $objectManager,
	  \Magento\Store\Model\StoreManagerInterface $storeManager,
	  \Magecomp\Restrictorder\Helper\Data $helperata,
	  \Psr\Log\LoggerInterface $logger
	  )
	  {
	  	 $this->objectManager = $objectManager;
		 $this->storeManager = $storeManager;
		 $this->_helperdata = $helperata;
		 $this->_logger = $logger;
	  }
 
	  public function execute(\Magento\Framework\Event\Observer $observer)
	  {
		 try
		 { 
			if($this->_helperdata->isEnabled())
			{
				$event  = $observer->getEvent();
				$quote	= $event->getQuote();
				$result = $event->getResult();
				if (!$quote)
					return;

				$shipping_address = $quote->getShippingAddress();
				$zipcode = $shipping_address->getData('postcode');
				$countryId = $shipping_address->getData('country_id');
				$city = $shipping_address->getData('city');

				$customerGroupId = $quote->getCustomerGroupId();

				if ($shipping_address && $countryId && $zipcode)
				{
					if ( $this->_helperdata->isOrderAllowed($countryId,$city,$zipcode,$customerGroupId))
					{
						$result->setData( 'is_available', true);
					}
					else
					{
						$result->setData( 'is_available', false);
					}
				}
			}
		 }
		 catch(\Exception $e)
		 {
			 $this->_logger->info($e->getMessage());
		 }
      }
}