<?php

namespace Magecomp\Restrictorder\Model\System;

use Magento\Customer\Model\Customer\Source\GroupFactory;
class Groups {

    protected $_customerGroupFactory;

    public function __construct(GroupFactory $customerGroupFactory)
    {
        $this->_customerGroupFactory = $customerGroupFactory;

    }

    public function toOptionArray()
    {
        $returnArray = $this->_customerGroupFactory->create()->toOptionArray();
        array_shift($returnArray);
        return $returnArray;
    }
}