<?php

namespace Magecomp\Restrictorder\Model;

class Restriction extends \Magento\Framework\Model\AbstractModel
{
    protected function _construct()
    {
        parent::_construct();
        $this->_init('Magecomp\Restrictorder\Model\ResourceModel\Restriction');
    }
}