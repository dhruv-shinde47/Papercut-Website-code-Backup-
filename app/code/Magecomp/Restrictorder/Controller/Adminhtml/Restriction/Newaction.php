<?php

namespace Magecomp\Restrictorder\Controller\Adminhtml\Restriction;

class Newaction extends \Magecomp\Restrictorder\Controller\Adminhtml\Restriction
{

    public function execute()
    {
        $this->_forward('edit');
    }
	protected function _isAllowed()
	{
		 return true;
    }
}