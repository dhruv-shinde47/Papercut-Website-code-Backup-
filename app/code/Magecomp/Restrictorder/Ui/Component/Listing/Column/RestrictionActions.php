<?php
namespace Magecomp\Restrictorder\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\UrlInterface;

class RestrictionActions extends Column
{
    const RESTRICTION_URL_PATH_EDIT = 'restrictorder/restriction/edit';
    const RESTRICTION_URL_PATH_DELETE = 'restrictorder/restriction/delete';

    protected $urlBuilder;

    private $editUrl;

    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        array $components = [],
        array $data = [],
        $editUrl = self::RESTRICTION_URL_PATH_EDIT
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->editUrl = $editUrl;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                $name = $this->getData('name');
       
                if (isset($item['restriction_id'])) {
                    $item[$name]['edit'] = [
                        'href' => $this->urlBuilder->getUrl($this->editUrl, ['id' => $item['restriction_id']]),
                        'label' => __('Edit')
                    ];
                    $item[$name]['delete'] = [
                        'href' => $this->urlBuilder->getUrl(self::RESTRICTION_URL_PATH_DELETE, ['id' => $item['restriction_id']]),
                        'label' => __('Delete'),
                        'confirm' => [
                            'title' => __('Delete Location'),
                            'message' => __('Are you sure you want to delete this location?')
                        ]
                    ];
                }
            }
        }
        return $dataSource;
    }
}