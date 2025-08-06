<?php

namespace MageArray\Formulaprice\Model;

use Magento\Catalog\Api\Data\ProductTierPriceInterfaceFactory;
use Magento\Customer\Api\GroupManagementInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Pricing\PriceCurrencyInterface;

class GetFinalPrice extends \Magento\Catalog\Model\Product\Type\Price
{
    public function __construct(
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        PriceCurrencyInterface $priceCurrency,
        GroupManagementInterface $groupManagement,
        ProductTierPriceInterfaceFactory $tierPriceFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $config,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Catalog\Model\ProductFactory $productModel,
        \Magento\Framework\Locale\Format $localeFormat,
		\MageArray\Formulaprice\Helper\Data $dataHelper,
        JsonFactory $resultJsonFactory
    ) {
        $this->_productRepository = $productRepository;
        $this->_eventManager = $eventManager;
        $this->_localeDate = $localeDate;
        $this->priceCurrency = $priceCurrency;
        $this->_groupManagement = $groupManagement;
        $this->tierPriceFactory = $tierPriceFactory;
        $this->config = $config;
        $this->_objectManager = $objectManager;
        $this->productModel = $productModel;
        $this->localeFormat = $localeFormat;
		$this->_dataHelper = $dataHelper;
        $this->resultJsonFactory = $resultJsonFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function price($items)
    {
        $product = $this->_productRepository->get($items['sku']);
        $qty = $items['qty'];
        if ($qty === null &&
            $product->getCalculatedFinalPrice() !== null) {
            return $product->getCalculatedFinalPrice();
        }

        $finalPrice = $product->getPriceInfo()
            ->getPrice('final_price')->getValue();
        $product->setFinalPrice($finalPrice);
        $event = "catalog_product_get_final_price";
        $this->_eventManager
            ->dispatch($event, ['product' => $product, 'qty' => $qty]);
        $finalPrice = $product->getData('final_price');
        // Added code to calculate custom option price - Start
        $finalPrice = $this->_applyCustomPrice($product, $qty, $finalPrice, $items);
        // Added code to calculate custom option price - End;

        $finalPrice = max(0, $finalPrice);
        $finalPrice = $this->localeFormat->getNumber($finalPrice, true);
        $finalPrice = $qty * $finalPrice;
        $product->setFinalPrice($finalPrice);
        $result = [];
        $result["price"]['final_price'] = $finalPrice;

        return $result;
    }

    public function _applyCustomPrice($products, $qty, $finalPrice, $items)
    {
        try {
            $pid = $products->getId();

            $product = $this->productModel->create()->load($pid);
            $active = $product->getFormulaPriceEnable();
            $equation = $product->getFormulaPriceFinal();
            $equation = strtolower($equation);
            $attributes = $product->getAttributes();
            $price = $product->getPrice();
            $attr = [];
            foreach ($attributes as $attribute) {
                $attrType = $attribute->getBackendType();
                if ($attrType != 'int' && $attrType != 'decimal') {
                    continue;
                }

                $attrCode = $attribute->getAttributeCode();
                $value = $product->getData($attrCode);
                if (is_string($value)) {
                    $attr[$attrCode] = $value;
                }
            }

            $extraFormula = $product->getExtraPriceFormula();
            $extraFormula = explode(';', $extraFormula);
            $extraPriceFormula = [];
            foreach ($extraFormula as $formula) {
                $formula = explode('=>', $formula);
                if (isset($formula[0]) && isset($formula[1])) {
                    $exKey = strtolower(trim((string)$formula[0]));
                    $exVal = trim((string)$formula[1]);
                    $extraPriceFormula[$exKey] = $exVal;
                }
            }

            $staticEquation = $product->getStaticVariableEquation();
            $staticEquations = explode(';', $staticEquation);
            foreach ($staticEquations as $staticEquation) {
                $staticEqua = explode('=>', $staticEquation);
                if (isset($staticEqua[0]) && isset($staticEqua[1])) {
                    $defineVar = "{" . strtolower(trim((string)$staticEqua[0])) . "}";
                    $defineEqu = trim((string)$staticEqua[1]);
                    if (strpos($equation, $defineVar) !== false) {
                        $equation = str_replace($defineVar, $defineEqu, $equation);
                    }
                }
            }

            $staticVariables = $product->getStaticFixedVariable();
            $staticVariables = explode(';', $staticVariables);
            $staticVar = [];
            foreach ($staticVariables as $staticVariable) {
                $staticVars = explode('=>', $staticVariable);
                if (isset($staticVars[0]) && isset($staticVars[1])) {
                    $stKey = strtolower(trim((string)$staticVars[0]));
                    $stVal = trim((string)$staticVars[1]);
                    $staticVar[$stKey] = $stVal;
                }
            }

            foreach ($staticVar as $key => $val) {
                $key = "{" . $key . "}";
                if (strpos($equation, $key) !== false) {
                    $equation = str_replace($key, $val, $equation);
                }
            }

            foreach ($attr as $akey => $aval) {
                $akey = "{" . $akey . "}";
                if (strpos($equation, $akey) !== false) {
                    $equation = str_replace($akey, $aval, $equation);
                }
            }

            if ($qty == '') {
                $qty = 1;
            }
            if ($active == 1) {
                $options=$items['product_option']['extension_attributes']['custom_options'];
                if ($options) {
                    $custVars = $product->getFormulaPriceVariable();
                    $custVar = explode(';', $custVars);
                    $finalOp = [];
                    $allLabel = [];
                    foreach ($custVar as $var) {
                        $label = explode('=>', $var);
                        if (isset($label[0]) && isset($label[1])) {
                            $fiKey = strtolower(trim((string)$label[0]));
                            $fiVal = trim((string)$label[1]);
                            $finalOp[$fiKey] = $fiVal;
                            $allLabel[] = trim($label[0]);
                        }
                    }
                    $custVals = $product->getAssignCustomOptionValue();
                    $custVal = explode(';', $custVals);
                    $finalVal = [];
                    foreach ($custVal as $opVal) {
                        $valLabel = explode('=>', $opVal);
                        if (isset($valLabel[0]) && isset($valLabel[1])) {
                            $fKey = strtolower(trim((string)$valLabel[0]));
                            $fVal = trim((string)$valLabel[1]);
                            $finalVal[$fKey] = $fVal;
                        }
                    }
                    $optionValues = [];

                    foreach ($options as $coption) {
                        if ($option = $products->getOptionById($coption['option_id'])) {
                            $title = $option->getTitle();
                            if (in_array($title, $allLabel)) {
                                $rowvalue = $coption['option_value'];
                                $setValue = $finalOp[strtolower(trim((string)$title))];
                                $setValue = strtolower(trim((string)$setValue));
                                if ($option->getType() == 'drop_down' ||
                                    $option->getType() == 'radio') {
                                    if ($option->getValues()) {
                                        foreach ($option->getValues() as $value) {
                                            $oId = $value->getOptionTypeId();
                                            if ($oId == $rowvalue) {
                                                $selectTitle = "{" . strtolower(trim($value->getDefaultTitle())) . "}";
                                                if (array_key_exists($selectTitle, $finalVal)) {
                                                    $optionValues[$setValue] = $finalVal[$selectTitle];
                                                } else {
                                                    if ($value->getPriceType() == "fixed") {
                                                        $optionValues[$setValue] = $value->getPrice();
                                                    }
                                                }
                                            }
                                        }
                                    }
                                } elseif ($option->getType() == 'multiple' ||
                                    $option->getType() == 'checkbox') {
                                    $rowvalue = explode(",", $rowvalue);
                                    if ($option->getValues()) {
                                        $optionFinalValue = 0;
                                        foreach ($option->getValues() as $value) {
                                            $oId = $value->getOptionTypeId();
                                            if (in_array($oId, $rowvalue)) {
                                                $selectTitle = "{" . strtolower(trim($value->getDefaultTitle())) . "}";
                                                if (array_key_exists($selectTitle, $finalVal)) {
                                                    $optionFinalValue = $optionFinalValue+$finalVal[$selectTitle];
                                                    $optionValues[$setValue] = $optionFinalValue;
                                                } else {
                                                    if ($value->getPriceType() == "fixed") {
                                                        $optionFinalValue = $optionFinalValue+$value->getPrice();
                                                        $optionValues[$setValue] = $optionFinalValue;
                                                    }
                                                }
                                            }
                                        }
                                    }
                                } else {
                                    $optionValues[$setValue] = $rowvalue;
                                }
                            } else {
                                $rowvalue = $coption['option_value'];
                                $setValue = strtolower(trim((string)$title));
                                if ($option->getType() == 'drop_down' ||
                                    $option->getType() == 'radio') {
                                    if ($option->getValues()) {
                                        foreach ($option->getValues() as $value) {
                                            $oId = $value->getOptionTypeId();
                                            if ($oId == $rowvalue) {
                                                $selectTitle = "{" . strtolower(trim($value->getDefaultTitle())) . "}";
                                                if (array_key_exists($selectTitle, $finalVal)) {
                                                    $optionValues[$setValue] = $finalVal[$selectTitle];
                                                } else {
                                                    if ($value->getPriceType() == "fixed") {
                                                        $optionValues[$setValue] = $value->getPrice();
                                                    }
                                                }
                                            }
                                        }
                                    }
                                } elseif ($option->getType() == 'multiple' ||
                                    $option->getType() == 'checkbox') {
                                    $rowvalue = explode(",", $rowvalue);
                                    if ($option->getValues()) {
                                        $optionFinalValue = 0;
                                        foreach ($option->getValues() as $value) {
                                            $oId = $value->getOptionTypeId();
                                            if (in_array($oId, $rowvalue)) {
                                                $selectTitle = "{" . strtolower(trim($value->getDefaultTitle())) . "}";
                                                if (array_key_exists($selectTitle, $finalVal)) {
                                                    $optionFinalValue = $optionFinalValue+$finalVal[$selectTitle];
                                                    $optionValues[$setValue] = $optionFinalValue;
                                                } else {
                                                    if ($value->getPriceType() == "fixed") {
                                                        $optionFinalValue = $optionFinalValue+$value->getPrice();
                                                        $optionValues[$setValue] = $optionFinalValue;
                                                    }
                                                }
                                            }
                                        }
                                    }
                                } else {
                                    $optionValues[$setValue] = $rowvalue;
                                }
                            }
                        }
                    }

                    $equation = strtolower($equation);
                    foreach ($optionValues as $key => $val) {
                        $getVar = '{' . $key . '}';
                        $equation = str_replace($getVar, $val, $equation);
                    }

                    foreach ($attr as $akey => $aval) {
                        $optionValues[$akey] = $aval;
                    }

                    $equation = str_replace("{qty}", $qty, $equation);
                    $equation = str_replace("{price}", $price, $equation);
                    $equation = preg_replace('#\{.*?\}#s', "0", $equation);
                    $finalPrice = $this->calculateString($equation);

                    foreach ($extraPriceFormula as $ekey => $eval) {
                        if (!preg_match('/[a-z]+/', $ekey)) {
                            $ekey = $this->calculateString($ekey);
                            if ($ekey == 1) {
                                foreach ($staticVar as $skey => $sval) {
                                    $skey = "{" . $skey . "}";
                                    if (strpos($eval, $skey) !== false) {
                                        $eval = str_replace($skey, $sval, $eval);
                                    }
                                }
                                $finalPrice = $finalPrice . '+ (' . str_replace('{newprice}', $finalPrice, $eval) . ')';
                                $finalPrice = $this->calculateString($finalPrice);
                                $finalPrice = number_format($finalPrice, 2);
                            }
                        } else {
                            foreach ($optionValues as $key => $val) {
                                $getVar = '{' . $key . '}';
                                if (strpos($ekey, $getVar) !== false) {
                                    $ekey = str_replace($getVar, $val, $ekey);

                                    if (!preg_match('/[a-z]+/', $ekey)) {
                                        $ekey = $this->calculateString($ekey);

                                        if ($ekey == 1) {
                                            foreach ($staticVar as $skey => $sval) {
                                                $skey = "{" . $skey . "}";
                                                if (strpos($eval, $skey) !== false) {
                                                    $eval = str_replace($skey, $sval, $eval);
                                                }
                                            }

                                            $finalPrice = $finalPrice . '+ (' . str_replace('{newprice}', $finalPrice, $eval) . ')';

                                            $finalPrice = $this->calculateString($finalPrice);
                                            $finalPrice = number_format($finalPrice, 2);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                return $finalPrice;
            } else {
                return $finalPrice;
            }
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()), $e);
        }
    }

    public function calculateString($mathString)
    {
        $mathString = trim($mathString);
        $mathString = preg_replace('[^0-9\+-\*\/\(\) ]', '', $mathString);
		$compute = $this->_dataHelper->execute($mathString);
        return 0 + $compute();
    }
}
