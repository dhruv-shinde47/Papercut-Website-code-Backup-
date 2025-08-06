<?php
namespace Magecomp\Restrictorder\Plugin;

use Magento\Payment\Model\MethodInterface;
use Magento\Quote\Model\Quote;

class Composite
{
    protected $helperdata;

    public function __construct(\Magecomp\Restrictorder\Helper\Data $helperdata) {
        $this->helperdata = $helperdata;
    }

    public function afterisApplicable(\Magento\Payment\Model\Checks\Composite $subject, $result, MethodInterface $paymentMethod, Quote $quote)
    {
        if(!$this->helperdata->isEnabled())
            return $result;

        $addresses = $quote->getAllShippingAddresses();
        foreach ($addresses as $address) {
            if ($address && $address->getData('country_id') && $address->getData('postcode'))
            {
                if (!$this->helperdata->isOrderAllowed($address->getData('country_id'),
                    $address->getData('city'),
                    $address->getData('postcode'),
                    $quote->getCustomerGroupId()))
                {
                    return false;
                }
            }
        }
        return $result;
    }
}
