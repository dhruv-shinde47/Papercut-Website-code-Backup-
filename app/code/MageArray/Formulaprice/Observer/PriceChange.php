<?php
namespace MageArray\Formulaprice\Observer;

use Magento\Framework\Event\ObserverInterface;

class PriceChange implements ObserverInterface
{
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $item = $observer->getEvent()->getData('quote_item');
        $item = ($item->getParentItem() ? $item->getParentItem() : $item);
        if ($item->getProduct()->getData('formula_price_enable') == 1) {
            
            $item->setCustomPrice($item->getProduct()->getFinalprice());
            $item->setOriginalCustomPrice($item->getProduct()->getFinalprice());
            $item->getProduct()->setIsSuperMode(true);
        }
    }
}
