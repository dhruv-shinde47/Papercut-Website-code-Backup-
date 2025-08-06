<?php

namespace Magecomp\Restrictorder\Controller\Adminhtml\Restriction;

class Save extends \Magecomp\Restrictorder\Controller\Adminhtml\Restriction
{
    public function execute()
    {
        if ($this->getRequest()->getPostValue())
        {
            try
            {
                $model = $this->_objectManager->create('Magecomp\Restrictorder\Model\Restriction');
                $data = $this->getRequest()->getPostValue();
                $inputFilter = new \Zend_Filter_Input(
                    [],
                    [],
                    $data
                );
                $data = $inputFilter->getUnescaped();
                $id = $this->getRequest()->getParam('id');
                if ($id) {
                    $model->load($id);
                    if ($id != $model->getId()) {
                        throw new \Magento\Framework\Exception\LocalizedException(__('Something went wrong !'));
                    }
                }
                $model->setData($data);
                $session = $this->_objectManager->get('Magento\Backend\Model\Session');
                $session->setPageData($model->getData());
                $model->save();
                $this->messageManager->addSuccess(__('Restriction Saved Successfully.'));
                $session->setPageData(false);
                if ($this->getRequest()->getParam('back'))
                {
                    $this->_redirect('restrictorder/*/edit', ['id' => $model->getId()]);
                    return;
                }
                $this->_redirect('restrictorder/*/');
                return;
            }
            catch (\Magento\Framework\Exception\LocalizedException $e)
            {
                $this->messageManager->addError($e->getMessage());
                $id = (int)$this->getRequest()->getParam('id');
                if (!empty($id))
                {
                    $this->_redirect('restrictorder/*/edit', ['id' => $id]);
                }
                else
                {
                    $this->_redirect('restrictorder/*/new');
                }
                return;
            }
            catch (\Exception $e)
            {
                $this->messageManager->addError(
                    __('Something went wrong while saving the restriction data.')
                );
                $this->_objectManager->get('Psr\Log\LoggerInterface')->critical($e);
                $this->_objectManager->get('Magento\Backend\Model\Session')->setPageData($data);
                $this->_redirect('restrictorder/*/edit', ['id' => $this->getRequest()->getParam('id')]);
                return;
            }
        }
        $this->_redirect('restrictorder/*/');
    }
	protected function _isAllowed()
	{
		 return true;
    }
}