<?php

namespace MageArray\Formulaprice\Plugin;

use Magento\Catalog\Api\Data\ProductTierPriceInterfaceFactory;
use Magento\Customer\Api\GroupManagementInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;


class FormulaPricePlugin
{
    public function __construct(
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        PriceCurrencyInterface $priceCurrency,
        GroupManagementInterface $groupManagement,
        ProductTierPriceInterfaceFactory $tierPriceFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $config,
        \Magento\Framework\Locale\Format $localeFormat,
		\MageArray\Formulaprice\Helper\Data $dataHelper,
        \Magento\Catalog\Model\ProductFactory $productloader
    ) {
        $this->_eventManager = $eventManager;
        $this->_localeDate = $localeDate;
        $this->priceCurrency = $priceCurrency;
        $this->_groupManagement = $groupManagement;
        $this->tierPriceFactory = $tierPriceFactory;
        $this->config = $config;
        $this->localeFormat = $localeFormat;
		$this->_dataHelper = $dataHelper;
        $this->productloader = $productloader;
    }

    public function aroundGetFinalPrice(
        \Magento\Catalog\Model\Product\Type\Price $subject,
        callable $proceed,
        $qty,
        $product
    ) {
        $finalPrice = $proceed($qty, $product);
        $result = $this->_applyCustomPrice($product, $qty, $finalPrice);
        $result = $this->localeFormat->getNumber($result, true);
        return $result;
    }

    public function _applyCustomPrice($products, $qty, $finalPrice)
    {
        try {
            $pid = $products->getId();
            $product = $this->productloader->create()->load($pid);
            $active = $product->getFormulaPriceEnable();
            $equation = $product->getFormulaPriceFinal();
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
                if ($optionIds = $products->getCustomOption('option_ids')) {
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
                    $opArray = explode(',', $optionIds->getValue());
                    foreach ($opArray as $optionId) {
                        if ($option = $products->getOptionById($optionId)) {
                            $title = $option->getTitle();
                            if (in_array($title, $allLabel)) {
                                $confItemOption = $products->getCustomOption('option_' . $optionId);
                                $rowvalue = $confItemOption->getValue();
                                $title = strtolower(trim((string)$title));
                                $setValue = $finalOp[$title];
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
                                                    $type = $value->getPriceType();
                                                    if ($type == "fixed") {
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
                                                    $type = $value->getPriceType();
                                                    if ($type == "fixed") {
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
                                $confItemOption = $products->getCustomOption('option_' . $optionId);
                                $rowvalue = $confItemOption->getValue();
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
                    $replaceFormula = [];
                    foreach ($extraPriceFormula as $ekey => $eval) {
                    
                        if (!preg_match('/[a-z]+/', $ekey) || !preg_match('/[a-z]+/', $eval)) {
                            
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
                                if (strpos($ekey, $getVar) !== false || strpos($eval, $getVar) !== false) {
                                    
                                        $ekey = str_replace($getVar, $val, $ekey);
                                    
                                    if (!preg_match('/[a-z]+/', $ekey)) {
                                        
                                        if (array_key_exists($ekey, $replaceFormula)) {
                                            $eval = str_replace($getVar, $val, $replaceFormula[$ekey]);
                                             $replaceFormula[$ekey] = $eval;
                                        } else {
                                            $eval = str_replace($getVar, $val, $eval);
                                            $replaceFormula[$ekey] = $eval;
                                        }
                                    }
                                }
                            }
                            
                        }
                    }
                    
                    if (!empty($replaceFormula)) {
						
                        foreach ($replaceFormula as $fkey => $fval) {
							
                             $fkey = $this->calculateString($fkey);
                                
                            if ($fkey == 1) {

                                foreach ($staticVar as $skey => $sval) {
                                    $skey = "{" . $skey . "}";
                                    if (strpos($fval, $skey) !== false) {
                                        $fval = str_replace($skey, $sval, $fval);
                                    }
                                }
                               
                                $finalPrice = $finalPrice . '+ (' . str_replace('{newprice}', $finalPrice, $fval) . ')';
                                $finalPrice = $this->calculateString($finalPrice);
                                $finalPrice = number_format($finalPrice, 2);
																
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
		$compute = eval("return (" . $mathString . ");"); 
        return 0 + $compute;
    }
}
