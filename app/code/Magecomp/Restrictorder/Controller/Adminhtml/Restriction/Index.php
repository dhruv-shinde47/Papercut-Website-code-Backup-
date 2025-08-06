<?php
namespace Magecomp\Restrictorder\Controller\Adminhtml\Restriction;

use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
class Index extends \Magento\Backend\App\Action
{
    protected $resultPageFactory;
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }

    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Magecomp_Restrictorder::restrictorder_template_ui');
        $resultPage->addBreadcrumb(__('Restriction'), __('Allowed Locations'));
        $resultPage->addBreadcrumb(__('Restriction'), __('Allowed Locations'));
        $resultPage->getConfig()->getTitle()->prepend(__('Allowed Locations'));

        return $resultPage;
    }

    protected function _isAllowed()
    {
        return true;
    }
}