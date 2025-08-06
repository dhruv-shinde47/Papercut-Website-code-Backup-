<?php

namespace Magecomp\Restrictorder\Block\Adminhtml\Restriction;

class Edit extends \Magento\Backend\Block\Widget\Form\Container
{
    protected $_coreRegistry = null;
	
	public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        parent::__construct($context, $data);
    }

    protected function _construct()
    {
        $this->_objectId = 'restriction_id';
        $this->_blockGroup = 'Magecomp_Restrictorder';
        $this->_controller = 'adminhtml_restriction';

        parent::_construct();

        $this->buttonList->update('save', 'label', __('Save'));
        $this->buttonList->add(
            'saveandcontinue',
            [
                'label' => __('Save and Continue Edit'),
                'class' => 'save',
                'data_attribute' => [
                    'mage-init' => [
                        'button' => ['event' => 'saveAndContinueEdit', 'target' => '#edit_form'],
                    ],
                ]
            ],
            -100
        );
    }
    public function getHeaderText()
    {
        if ($this->_coreRegistry->registry('restrictorder_data')->getId()) {
            return __("Edit Location '%1'", $this->escapeHtml($this->_coreRegistry->registry('restrictorder_data')->getTitle()));
        } else {
            return __('New Location');
        }
    }
    protected function _isAllowedAction($resourceId)
    {
        return $this->_authorization->isAllowed($resourceId);
    }

    protected function _getSaveAndContinueUrl()
    {
        return $this->getUrl('*/*/save', ['_secure' => true, '_current' => true, 'back' => 'edit', 'active_tab' => '{{tab_id}}']);
    }
	
	public function getDeleteUrl(array $args = [])
	{
		return $this->getUrl('*/*/delete', ['_secure' => true, '_current' => true, 'back' => 'edit', 'active_tab' => '{{tab_id}}']);
	}

    protected function _prepareLayout()
    {
        $this->_formScripts[] = "
            function toggleEditor() {
                if (tinyMCE.getInstanceById('page_content') == null) {
                    tinyMCE.execCommand('mceAddControl', false, 'page_content');
                } else {
                    tinyMCE.execCommand('mceRemoveControl', false, 'page_content');
                }
            };
        ";
        return parent::_prepareLayout();
    }
}