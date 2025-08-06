<?php 
namespace Magecomp\Restrictorder\Helper;

use Magecomp\Restrictorder\Model\RestrictionFactory;
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
	const RESTRICTION_ENABLED = 'restrictorder/settings/enable';
	const RESTRICTION_GROUP_FILER_ENABLED = 'restrictorder/settings/iscustomergroup';
	const RESTRICTION_CUSTOMER_GROUP = 'restrictorder/settings/customergroup';
	public function __construct(
		\Magento\Framework\App\Helper\Context $context,
		RestrictionFactory $modelRestrictionFactory
	) {
		$this->_modelRestrictionFactory = $modelRestrictionFactory;
		parent::__construct($context);
	}
	public function isEnabled()
	{
		return $this->scopeConfig->getValue(
			self::RESTRICTION_ENABLED, \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
	public function isApplyGroupFilter()
	{
		return $this->scopeConfig->getValue(
			self::RESTRICTION_GROUP_FILER_ENABLED, \Magento\Store\Model\ScopeInterface::SCOPE_STORE
		);
	}
	public function isValidCustomerGroup($customerGroupId)
	{
		$groupFilter = explode(',', $this->scopeConfig->getValue(self::RESTRICTION_CUSTOMER_GROUP, \Magento\Store\Model\ScopeInterface::SCOPE_STORE));
		if(in_array($customerGroupId, $groupFilter))
		{
			return true;

		} else {
			return false;
		}
	}
	public function isOrderAllowed($countryId,$city,$zipcode,$customerGroupId)
	{
		  $codModel = $this->_modelRestrictionFactory->create();
		  $collection = $codModel->getCollection();
		  $count=0;
		  $apply = true;
		  if($this->isApplyGroupFilter())
		  {
			  if(!$this->isValidCustomerGroup($customerGroupId))
			  {
				  $apply = false;
			  }
		  }
		  if($apply)
		  {
			 foreach ($collection as $row)
			 {
				 if (($row->getCountryId() == $countryId) || $row->getCountryId() == "*")
				 {
					 if ((strtolower($row->getCity()) == strtolower($city)) || $row->getCity() == "*")
					 {
						 if (($row->getZipCode() == $zipcode) || $row->getZipCode() == "*")
						 {
							 $count++;
						 }
					 }
				 }
			 }
		  }
		  else {
			  return true;
		  }
		  if($count != 0){
			return true;
		  }
		  else{
			return false;
		  }
	}
}