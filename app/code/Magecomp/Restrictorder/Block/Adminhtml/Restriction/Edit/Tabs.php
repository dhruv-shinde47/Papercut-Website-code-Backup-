<?php

namespace Magecomp\Restrictorder\Block\Adminhtml\Restriction\Edit;

class Tabs extends \Magento\Backend\Block\Widget\Tabs
{
	protected function _construct()
	{
        parent::_construct();
        $this->setId('page_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(__('Location Information'));
    }
}