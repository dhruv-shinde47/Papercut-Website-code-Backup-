<?php
namespace Magecomp\Restrictorder\Controller\Adminhtml;
abstract class Restriction extends \Magento\Backend\App\Action
{
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magecomp_Restrictorder::restrictorder_restriction');
    }
     
}