<?php
namespace Magecomp\Restrictorder\Block\Adminhtml\System\Config\Form\Field;

class Import extends \Magento\Framework\Data\Form\Element\AbstractElement
{
    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setType('file');
    }

    /**
     * Enter description here...
     *
     * @return string
     */
    public function getElementHtml()
    {
        $html = '';
        $html .= parent::getElementHtml();
        return $html;
    }
}