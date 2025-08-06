<?php
namespace Magecomp\Restrictorder\Controller\Adminhtml\Restriction;

use Magecomp\Restrictorder\Model\RestrictionFactory;
use Magento\Backend\App\Action;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Registry;

class Edit extends Action
{
    protected $modelRestrictionFactory;
	protected $_coreRegistry = null;
	protected $resultPageFactory;

    public function __construct(Action\Context $context,
		                        PageFactory $resultPageFactory,
		                        Registry $registry,
                                RestrictionFactory $modelRestrictionFactory)
    {
		$this->resultPageFactory = $resultPageFactory;
        $this->_coreRegistry = $registry;
        $this->modelRestrictionFactory = $modelRestrictionFactory;

        parent::__construct($context);
    }
	
	protected function _isAllowed()
	{
		 return true;
    }
	
	protected function _initAction()
	{
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Magecomp_Restrictorder::restrictorder')
            ->addBreadcrumb(__('Restrict Order'), __('Restrict Order'))
            ->addBreadcrumb(__('Allowed Location'), __('Allowed Location'));
        return $resultPage;
    }

	public function execute()
	{
        $id = $this->getRequest()->getParam('id');
		$model  = $this->modelRestrictionFactory->create();

        if ($id)
        {
            $model->load($id);
            if (!$model->getId())
            {
                $this->messageManager->addError(__('This Restriction is no longer exists.'));
                $resultRedirect = $this->resultRedirectFactory->create();
                return $resultRedirect->setPath('*/*/');
            }
        }

        $data = $this->_objectManager->get('Magento\Backend\Model\Session')->getFormData(true);
		
        if (!empty($data)) {
            $model->setData($data);
        }

        $this->_coreRegistry->register('restrictorder_data', $model);

        $resultPage = $this->_initAction();

        $resultPage->addBreadcrumb(
            $id ? __('Edit Location') : __('New Location'),
            $id ? __('Edit Location') : __('New Location')
        );
        $resultPage->getConfig()->getTitle()->prepend(__('Edit Location'));

        return $resultPage;
    }
}