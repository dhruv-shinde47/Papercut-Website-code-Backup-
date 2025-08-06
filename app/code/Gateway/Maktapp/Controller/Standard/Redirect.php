<?php

namespace Gateway\Maktapp\Controller\Standard;

class Redirect extends \Gateway\Maktapp\Controller\Maktapp
{
    public function execute()
    {
        $order = $this->getOrder();
        if ($order->getBillingAddress())
        {
			$this->addOrderHistory($order,'<br/>The customer was redirected to Maktapp');
			echo $this->getMaktappModel()->buildMaktappRequest($order);
            /*$this->getResponse()->setRedirect(
                $this->getMaktappModel()->buildMaktappRequest($order)
            );*/
        }
        else
        {
            $this->_cancelPayment();
            $this->_maktappSession->restoreQuote();
            $this->getResponse()->setRedirect(
                $this->getMaktappHelper()->getUrl('checkout')
            );
        }
    }
}