<?php
namespace Magecomp\Restrictorder\Controller\Adminhtml\Restriction;

abstract class AbstractRestriction extends \Magento\Backend\App\Action
{
 	protected function _isAllowed()  {
        return true;
    } 
}
