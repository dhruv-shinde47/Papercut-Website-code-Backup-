<?php

namespace Magecomp\Restrictorder\Model\ResourceModel\Restriction;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
	protected $_idFieldName = 'restriction_id';
    protected function _construct()
    {
        $this->_init('Magecomp\Restrictorder\Model\Restriction', 'Magecomp\Restrictorder\Model\ResourceModel\Restriction');
    }
}
