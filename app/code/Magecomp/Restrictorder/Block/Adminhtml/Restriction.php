<?php
namespace Magecomp\Restrictorder\Block\Adminhtml;

class Restriction extends \Magento\Backend\Block\Widget\Grid\Container
{
	protected function _construct()
	{
        $this->_controller = 'adminhtml_restriction';
        $this->_blockGroup = 'Magecomp_Restrictorder';
        $this->_headerText = __('Location Information Manager');

        parent::_construct();
    }
	
	protected function _isAllowedAction($resourceId)
	{
        return $this->_authorization->isAllowed($resourceId);
    }
}