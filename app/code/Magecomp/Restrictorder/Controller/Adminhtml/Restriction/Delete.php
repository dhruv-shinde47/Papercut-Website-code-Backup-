<?php
namespace Magecomp\Restrictorder\Controller\Adminhtml\Restriction;

use Magecomp\Restrictorder\Model\RestrictionFactory;
use Magento\Backend\App\Action\Context;

class Delete extends AbstractRestriction
{
    protected $_modelRestrictionFactory;

    public function __construct(Context $context,
								RestrictionFactory $modelRestrictionFactory)
    {
        $this->_modelRestrictionFactory = $modelRestrictionFactory;

        parent::__construct($context);
    }

    public function execute()
    {
		if($this->getRequest()->getParam('id') > 0)
		{
		  	try
		  	{
			  	$restrictionModel = $this->_modelRestrictionFactory->create();
			  	$restrictionModel->setId($this->getRequest()->getParam('id'))
							   ->delete();
			  	$this->messageManager->addSuccess('Restriction is successfully deleted.');
			  	$this->_redirect('*/*/');
		   	}
		   	catch (\Exception $e)
			{
				$this->messageManager->addError($e->getMessage());
				$this->_redirect('*/*/edit', ['id' => $this->getRequest()->getParam('id')]);
			}
	   	}
	  	$this->_redirect('*/*/');
	}
}