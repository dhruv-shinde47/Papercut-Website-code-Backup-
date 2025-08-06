<?php

namespace Magecomp\Restrictorder\Model\ResourceModel;


class Restriction extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
	 public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        $resourcePrefix = null)
	{
        parent::__construct($context, $resourcePrefix);     
    }
	protected function _construct()
	{
        $this->_init('magecomp_restrictorder', 'restriction_id');
    }
	
	
	
}
