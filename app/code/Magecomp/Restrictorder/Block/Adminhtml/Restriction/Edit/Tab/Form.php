<?php
namespace Magecomp\Restrictorder\Block\Adminhtml\Restriction\Edit\Tab;

use Magento\Backend\Block\Template\Context;
use Magento\Framework\Registry;
use Magento\Framework\Data\FormFactory;
use Magento\Store\Model\System\Store;
use Magento\Directory\Model\Config\Source\Country;
class Form extends \Magento\Backend\Block\Widget\Form\Generic implements \Magento\Backend\Block\Widget\Tab\TabInterface
{
 	protected $_systemStore;
	protected $_countryFactory;
	public function __construct(
		Context $context,
		Registry $registry,
		FormFactory $formFactory,
		Store $systemStore,
		Country $countryFactory,
		array $data = []
	) 
	{
		$this->_systemStore = $systemStore;
		$this->_countryFactory = $countryFactory;
		parent::__construct($context, $registry, $formFactory, $data);
	}
	protected function _prepareForm()
	{
		$model = $this->_coreRegistry->registry('restrictorder_data');
		$form = $this->_formFactory->create();

		$fieldset = $form->addFieldset('base_fieldset', ['legend' => __('Location Information')]);

		if ($model->getId()) 
		{
			$fieldset->addField('restriction_id', 'hidden', ['name' => 'restriction_id']);
		}
		$countryCollection=$this->_countryFactory->toOptionArray();
		$fieldset->addField(
			'country_id',
			'select',
			[
				'name' => 'country_id',
				'label' => __('Country'),
				'title' => __('Country'),
				'required' => true,
				'values' => $countryCollection,

			]
		);
		$fieldset->addField('city', 'text',
			[
				'label' => __('City'),
				'required' => true,
				'name' => 'city',
			]);
        $fieldset->addField('zip_code', 'text',
             [
					'label' => __('Zip Code'),
					'required' => true,
					'name' => 'zip_code',
        ]);
		$this->_eventManager->dispatch('restrictorder_restriction_edit_tab_form_prepare_form', ['form' => $form]);
		$form->setValues($model->getData());
		$this->setForm($form);
		return parent::_prepareForm();
	}
	public function getTabLabel()
	{
		return __('Edit Location Information');
	}

	public function getTabTitle()
	{
		return __('Edit Location Information');
	}

	public function canShowTab()
	{
		return true;
	}
	
	public function isHidden()
	{
		return false;
	}

	protected function _isAllowedAction($resourceId)
	{
		return $this->_authorization->isAllowed($resourceId);
	}
}