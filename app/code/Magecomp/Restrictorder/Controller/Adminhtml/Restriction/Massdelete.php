<?php
namespace Magecomp\Restrictorder\Controller\Adminhtml\Restriction;

use Magento\Backend\App\Action\Context;
use Magento\Ui\Component\MassAction\Filter;
use Magecomp\Restrictorder\Model\ResourceModel\Restriction\CollectionFactory;

class Massdelete extends \Magento\Backend\App\Action
{
	protected $filter;
	protected $_collectionFactory;
	public function __construct(
        Context $context,
		Filter $filter,
        CollectionFactory $collectionFactory
    ) {
        parent::__construct($context);
		$this->_collectionFactory = $collectionFactory;
		$this->filter = $filter;
    }
	public function execute()
	{
		$collection = $this->filter->getCollection($this->_collectionFactory->create());
		$collectionSize = $collection->getSize();
		foreach($collection as $item)
		{
			$item->delete();
		}
		$this->messageManager->addSuccess(__('A total of %1 record(s) have been deleted.', $collectionSize));
		$resultRedirect = $this->resultRedirectFactory->create();
		return $resultRedirect->setPath('*/*/');
	}
	protected function _isAllowed()
	{
		 return true;
    }
}