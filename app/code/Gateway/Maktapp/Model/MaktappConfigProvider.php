<?php

namespace Gateway\Maktapp\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Framework\UrlInterface as UrlInterface;

class MaktappConfigProvider implements ConfigProviderInterface
{
    protected $methodCode = "maktapp";

    protected $method;
    
    protected $urlBuilder;

    public function __construct(PaymentHelper $paymentHelper, UrlInterface $urlBuilder) {
        $this->method = $paymentHelper->getMethodInstance($this->methodCode);
        $this->urlBuilder = $urlBuilder;
    }

    public function getConfig()
    {
        return $this->method->isAvailable() ? [
            'payment' => [
                'maktapp' => [
                    //'redirectUrl' => $this->urlBuilder->getUrl('makttapp/Standard/Redirect', ['_secure' => true])
                    'redirectUrl' => $this->urlBuilder->getUrl('maktapp/Standard/Redirect', ['_secure' => false])
                ]
            ]
        ] : [];
    }

    protected function getRedirectUrl()
    {
        return $this->_urlBuilder->getUrl('paypal/ipn/');
    }
    
    protected function getFormData()
    {
        return $this->method->getRedirectUrl();
    }
}
