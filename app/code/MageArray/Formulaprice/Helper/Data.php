<?php

namespace MageArray\Formulaprice\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     *	To Check Formula Price Enable
     */
    const XML_PATH_FORMULAPRICE_ENABLE = 'formulaprice/general/enable';
    /**
     * To Check Formula Price Add Qty
     */
    const XML_PATH_FORMULAPRICE_SHOW_QTY = 'formulaprice/general/add_qty';
    

    /**
     * @return mixed
     */
    public function getModuleEnable($store = null)
    {
        return $this->scopeConfig
            ->getValue(
                self::XML_PATH_FORMULAPRICE_ENABLE,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $store
            );
    }

    /**
     * @return mixed
     */
    public function getAddQty($store = null)
    {
        return $this->scopeConfig
            ->getValue(
                self::XML_PATH_FORMULAPRICE_SHOW_QTY,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $store
            );
    }   
}
