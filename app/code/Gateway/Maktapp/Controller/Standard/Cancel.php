<?php

namespace Gateway\Maktapp\Controller\Standard;

class Cancel extends \Gateway\Maktapp\Controller\Maktapp
{

    public function execute()
    {
        $this->_cancelPayment();
        $this->_checkoutSession->restoreQuote();
        $this->getResponse()->setRedirect(
            $this->getMaktappHelper()->getUrl('checkout')
        );
    }

}
