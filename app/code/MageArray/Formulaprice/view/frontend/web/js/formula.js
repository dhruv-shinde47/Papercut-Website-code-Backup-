	define([
		"jquery",
		"Magento_Catalog/js/price-utils",
		'jquery/ui',
		'jquery/validate',
		'mage/translate'
	], function(jQuery,priceUtils) {
		 'use strict';
		return function (config) {
				if ( typeof config.page !== 'undefined' && config.page == 'updatecart' ) {
					jQuery('body').loader('show');
				}
				setTimeout(function(){ 
				calculatePrice(config); 
				jQuery('#qty').trigger("change")
				}, 4000);
		}

		function calculatePrice(config){
			var pageName = config.page;
			var productData = config.productData;
			var labels = productData.allLabel;
			var custValue = productData.allValue;
			var minCheck = productData.min;
			var maxCheck = productData.max;
			var maxMessage = productData.maxError;
			var minMessage = productData.minError;
			var extraPriceFormula = productData.extraFormula;
			var addQty = productData.addQty;
			var proPrice = productData.proPrice;
			var taxVat = productData.taxVat;
			var isExcludingPrice = productData.isExcludingPrice;
			var finalPrice = '';
			var basePrice = '';
			var configPrice = '';
			var qty = 1;
			
			jQuery('.product-custom-option').change(function(){
				if(productData.enable == 1){
					var equation = productData.finalEquation;
					equation = equation.trim().toLowerCase();
					finalPrice = getFinalPrice(productData.options, equation, labels, minCheck, maxCheck, maxMessage, minMessage, extraPriceFormula, qty, addQty, custValue, proPrice);
				}
				if(taxVat && isExcludingPrice != true){
					basePrice=finalPrice;
					finalPrice= finalPrice + ((finalPrice * taxVat)/100);
				}
				jQuery('[data-role=priceBox]').trigger('updatePrice');
			})
			jQuery('#qty').change(function(){
				if(productData.enable == 1){
					var equation = productData.finalEquation;
					equation = equation.trim().toLowerCase();
					qty = jQuery(this).val();
					finalPrice = getFinalPrice(productData.options, equation, labels, minCheck, maxCheck, maxMessage, minMessage, extraPriceFormula, qty, addQty, custValue, proPrice);
					
				}
				if(taxVat && isExcludingPrice != true){
					basePrice=finalPrice;
					finalPrice= finalPrice + ((finalPrice * taxVat)/100);
				}
				jQuery('[data-role=priceBox]').trigger('updatePrice');
			})
			
			jQuery('[data-role=priceBox]').on('reloadPrice', function(ev){
				var finalPriceObj = jQuery('.product-info-main [data-price-type="finalPrice"] .price');
				var basePriceObj = jQuery('.product-info-main .price-box.price-final_price [data-price-type="basePrice"] .price');
				if(finalPrice > 0){
					finalPriceObj.text(priceUtils.formatPrice(finalPrice));
				}
				if(basePrice > 0){
					basePriceObj.text(priceUtils.formatPrice(basePrice));
				}
			});
			if ( typeof pageName !== 'undefined' && pageName == 'updatecart' ) {
					jQuery('body').loader('hide'); 
			}
		}
		
		function getFinalPrice(alloptions, equation, labels, minCheck, maxCheck, maxMessage, minMessage, extraPriceFormula, getQty, addQty, custValue, proPrice) {
			var fixQty = 1;
			var finalPrice = 0;
			var msg = '';
			var maxMsg = '';
			var extraFormulas = [];
			var extraCount = 0;
			var extraFormulasEquation = [];
			var extraFormulasEquationValue = [];
			var extraFormulasEq = [];
			var oldcondition = '';
			var extraval = '';
			var finalPrices = '';
			jQuery.each(alloptions, function(i) {
				var value = '';
				var oLabel = alloptions[i].label;
				oLabel = oLabel.trim().toLowerCase();
				if (alloptions[i].type == 'field' || alloptions[i].type == 'area') {
					var textOptionId = '#options_' + alloptions[i].id + '_text';
					var checkLabel = '';
					if(oLabel in labels){
						var checkLabel = '{'+labels[oLabel]+'}';
						var csku = "{"+labels[oLabel]+"}";
						csku = csku.toLowerCase();
						checkLabel = checkLabel.toLowerCase();	   
						value = jQuery(textOptionId).val();
						if(checkLabel in minCheck){
							var vldata = jQuery(textOptionId).data("validate");
							if (!("validate-min-value-col" in vldata)) {
								var newObj = {"validate-min-value-col": true};
								jQuery.extend(vldata, newObj); //merged both in data variable
								jQuery(textOptionId).attr("data-validate", JSON.stringify(vldata));
							} 
					
							var values = jQuery(textOptionId).val();
							var minVal = minCheck[checkLabel];
							var msg = minMessage;
						}
						if(checkLabel in maxCheck){
							if (!("validate-max-value-col" in vldata)) {
								var newObj = {"validate-max-value-col": true};
								jQuery.extend(vldata, newObj); //merged both in data variable
								jQuery(textOptionId).attr("data-validate", JSON.stringify(vldata));
							} 
							var values = jQuery(textOptionId).val();
							var maxVal = maxCheck[checkLabel];
							var maxMsg = maxMessage;
						}
						 
					} else {
						var checkLabel = '{'+oLabel+'}';
						var csku = "{"+oLabel+"}";
						csku = csku.toLowerCase();
						checkLabel = checkLabel.toLowerCase();	   
						value = jQuery(textOptionId).val();
						if(checkLabel in minCheck){
							var vldata = jQuery(textOptionId).data("validate");
							if (!("validate-min-value-col" in vldata)) {
								var newObj = {"validate-min-value-col": true};
								jQuery.extend(vldata, newObj); //merged both in data variable
								jQuery(textOptionId).attr("data-validate", JSON.stringify(vldata));
							} 
					
							var values = jQuery(textOptionId).val();
							var minVal = minCheck[checkLabel];
							var msg = minMessage;
						}
						if(checkLabel in maxCheck){
							if (!("validate-max-value-col" in vldata)) {
								var newObj = {"validate-max-value-col": true};
								jQuery.extend(vldata, newObj); //merged both in data variable
								jQuery(textOptionId).attr("data-validate", JSON.stringify(vldata));
							} 
							var values = jQuery(textOptionId).val();
							var maxVal = maxCheck[checkLabel];
							var maxMsg = maxMessage;
						}
					}
					
				}
				
				if (alloptions[i].type == 'drop_down') {
					var dropId = '#select_' + alloptions[i].id;
					var dropValue = jQuery(dropId).val();
					var droLabel = jQuery(dropId+" option:selected").text();
					droLabel = droLabel.split('+$');
					droLabel = "{"+droLabel[0].trim().toLowerCase()+"}";
					if(oLabel in labels){
						var csku = "{"+labels[oLabel]+"}";
						csku = csku.toLowerCase();		   
						if(dropValue){
							if(droLabel in custValue){
								value = custValue[droLabel];
							} else {
								value = jQuery(dropId+" option:selected").attr("price");
							}
						}
					} else {
						var csku = "{"+oLabel+"}";
						csku = csku.toLowerCase();		   
						if(dropValue){
							if(droLabel in custValue){
								value = custValue[droLabel];
							} else {
								value = jQuery(dropId+" option:selected").attr("price");
							}
						}
					}
				}
				
				if (alloptions[i].type == 'multiple') {
					var multiId = '#select_' + alloptions[i].id;
					var multiValue = jQuery(multiId).val();
					var multiOption = jQuery(multiId+" option:selected");
					var newValue = 0;
					var csku = "";
					jQuery.each(multiOption, function(i) {
						var multiLabel = multiOption[i].text;
						multiLabel = multiLabel.split('+$');
						multiLabel = "{"+multiLabel[0].trim().toLowerCase()+"}";
						var multivalue = multiOption[i].value;
						if(oLabel in labels){
							csku = "{"+labels[oLabel]+"}";
							csku = csku.toLowerCase();
							if(multiLabel in custValue){
								var val = custValue[multiLabel];
								newValue = parseFloat(newValue)+parseFloat(val);
							} else {
								var val = jQuery('[value="'+multivalue+'"]').attr("price");
								newValue = parseFloat(newValue)+parseFloat(val);
							}
						} else {
							csku = "{"+oLabel+"}";
							csku = csku.toLowerCase();
							if(multiLabel in custValue){
								var val = custValue[multiLabel];
								newValue = parseFloat(newValue)+parseFloat(val);
							} else {
								var val = jQuery('[value="'+multivalue+'"]').attr("price");
								newValue = parseFloat(newValue)+parseFloat(val);
								
							}
						}
					});
					value = newValue;
				} 
				
				if (alloptions[i].type == 'checkbox') {
					var checkField = '[name="options[' + alloptions[i].id+'][]"]';
					var checkValue = jQuery(checkField+":checked");
					var newValue = 0;
					var csku = "";
					
					jQuery.each(checkValue, function(i) {
						var checkId = checkValue[i].id;
						var checkLabel = jQuery('[for="'+checkId+'"]').text();
						checkLabel = checkLabel.split('+');
					
						checkLabel = "{"+checkLabel[0].trim().toLowerCase()+"}";
						var checkvalue = checkValue[i].value;
						
						if(oLabel in labels){
							csku = "{"+labels[oLabel]+"}";
							csku = csku.toLowerCase();
							if(checkLabel in custValue){
								var val = custValue[checkLabel];
								newValue = parseFloat(newValue)+parseFloat(val);
							} else {
								var val = jQuery('[value="'+checkvalue+'"]').attr("price");
								newValue = parseFloat(newValue)+parseFloat(val);
							}
						} else {
							csku = "{"+oLabel+"}";
							csku = csku.toLowerCase();
							if(checkLabel in custValue){
								var val = custValue[checkLabel];
								newValue = parseFloat(newValue)+parseFloat(val);
							} else {
								var val = jQuery('[value="'+checkvalue+'"]').attr("price");
								newValue = parseFloat(newValue)+parseFloat(val);
								
							}
						} 
					}); 
					value = newValue;
				} 
				
				
				if (alloptions[i].type == 'radio') {
					var radioField = '[name="options[' + alloptions[i].id+']"]';
					var radioValue = jQuery(radioField+":checked").val();
					var radioId = jQuery(radioField+":checked").attr("id");
					if(radioId) {
						var radioLabel = jQuery('[for="'+radioId+'"]').text();
						radioLabel = radioLabel.split('+');
					
						radioLabel = "{"+radioLabel[0].trim().toLowerCase()+"}";
						
						if(oLabel in labels){
							
							var csku = "{"+labels[oLabel]+"}";
							csku = csku.toLowerCase();
							if(radioValue){
								if(radioLabel in custValue){
									value = custValue[radioLabel];
								} else {
									value = jQuery(radioField+":checked").attr("price");
								}
							}
						} else {
							var csku = "{"+oLabel+"}";
							csku = csku.toLowerCase();
							if(radioValue){
								if(radioLabel in custValue){
									value = custValue[radioLabel];
								} else {
									value = jQuery(radioField+":checked").attr("price");
								}
							}
						}
					
					}	

				}
				
				if(value > 0){

					if(finalPrice == 0){
						finalPrice = equation.replace(new RegExp(csku, 'g'), value);
					}else{
						finalPrice = finalPrice.replace(new RegExp(csku, 'g'), value);
					}
					 if(extraPriceFormula){
						var extraPriceFormulas = extraPriceFormula.split(';');
						console.log('extraPriceFormulas'+extraPriceFormulas);
						if(extraCount == 0){
							jQuery.each(extraPriceFormulas, function(arrKey, arrValue) {
								arrValue = arrValue.toLowerCase();				
								arrValue = arrValue.split('=>');
								extraFormulasEq[arrValue[0]]=arrValue[0];
								extraFormulasEquation[arrValue[0]]=arrValue[0];
								extraFormulasEquationValue[arrValue[1]]=arrValue[1];
							});
							extraCount = 1;
						 }
						jQuery.each( extraPriceFormulas, function( arrKey, arrValue) {
							arrValue = arrValue.toLowerCase();				
							arrValue = arrValue.split('=>');
							if(extraFormulasEq[arrValue[0]] == arrValue[0]){
								if (arrValue[0].indexOf(csku) > -1 || arrValue[1].indexOf(csku) > -1) {	
									if(arrValue[0].indexOf(csku) > -1){
										oldcondition = extraFormulasEquation[arrValue[0]].replace(new RegExp(csku, 'g'), value);	
									}else{
										oldcondition = extraFormulasEquation[arrValue[0]];
									}
									
									if(arrValue[1].indexOf(csku) > -1){
										extraval = extraFormulasEquationValue[arrValue[1]].replace(new RegExp(csku, 'g'), value);	
									}else{
										extraval = extraFormulasEquationValue[arrValue[1]];
									}
									
									extraFormulasEquation[arrValue[0]] = oldcondition;
									extraFormulasEquationValue[arrValue[1]] = extraval;
									 condition = oldcondition.replace(new RegExp("{[a-zA-Z0-9_]+}", "gi"), 0);
									 condition = eval(condition);
									if(condition == true){
										extraFormulas[arrValue[0]] = extraFormulasEquationValue[arrValue[1]];
									 } else {
										 delete extraFormulas[arrValue[0]];
									 }
								 }

								 if(extraFormulasEquation[arrValue[0]].search(/[a-zA-Z]/i) == -1){
									 var condition = extraFormulasEquation[arrValue[0]];
									 condition = eval(condition);
									if(condition == true){
										extraFormulas[arrValue[0]] = extraFormulasEquationValue[arrValue[1]];
									 } else {
										 delete extraFormulas[arrValue[0]];
									 }
								 }
							}
						});
					}
				}
				
			});
			var changePrice = "{price}";
			if(finalPrice == 0){
				finalPrice = equation.replace(new RegExp(changePrice, 'g'), proPrice);
			}else{
				finalPrice = finalPrice.replace(new RegExp(changePrice, 'g'), proPrice);
			}
			 jQuery.validator.addMethod("validate-min-value-col", function (currValue, element) {
				var currId = jQuery(element).attr('id');
				currId = currId.replace('options_', '');
				currId = currId.replace('_text', '');
				var label = alloptions[currId]['label'];
				label = label.trim().toLowerCase();
				var labelCheck = "";		   
				if(label in labels){
					labelCheck = '{'+labels[label]+'}';
				} else {
					labelCheck = '{'+label+'}';
				}
				labelCheck = labelCheck.trim().toLowerCase();
				var minVal = minCheck[labelCheck];
				msg = minMessage.replace('{min}', minVal);
				msg = msg.replace('{title}', label);
				if(parseFloat(currValue) < parseFloat(minVal)){
					return false;
				}
				return true;
			}, function () {
				return jQuery.mage.__(msg);
			});
			jQuery.validator.addMethod("validate-max-value-col", function (currValue, element) {
				var currId = jQuery(element).attr('id');
				currId = currId.replace('options_', '');
				currId = currId.replace('_text', '');
				var label = alloptions[currId]['label'];
				label = label.trim().toLowerCase();
				var labelCheck = "";
				if(label in labels){
					labelCheck = '{'+labels[label]+'}';
				} else {
					labelCheck = '{'+label+'}';
				}			
				labelCheck = labelCheck.trim().toLowerCase();
				var maxVal = maxCheck[labelCheck];
				maxMsg = maxMessage.replace('{max}', maxVal);
				maxMsg = maxMsg.replace('{title}', label);
				if(parseFloat(currValue) > parseFloat(maxVal)){
					return false;
				}
				return true;
			}, function () {
				return jQuery.mage.__(maxMsg);
			});
			finalPrice = finalPrice.replace(new RegExp("{[a-zA-Z0-9_.\"'!:*?()/ /g]+}", "gi"), 0);
			finalPrice = eval(finalPrice);
			if(finalPrice > 0){
				if(Object.keys(extraFormulas).length > 0){
					Object.keys(extraFormulas).forEach(function(extraEqkey,  extraEqValue) {
					
						if(typeof this[extraEqkey]  !== "undefined") {
							var extraEqFinalPriceCalc = this[extraEqkey].replace('{newprice}',finalPrice);
							extraEqFinalPriceCalc = extraEqFinalPriceCalc.replace(new RegExp("{[a-zA-Z0-9_]+}", "gi"), 0); 
							var extraEqFinalPrice = eval(extraEqFinalPriceCalc);
							
							finalPrice = finalPrice+'+'+extraEqFinalPrice;
						}
						finalPrice = eval(finalPrice);
					}, extraFormulas);
				}
			}
			finalPrices = Number(Math.round(finalPrice+'e2')+'e-2');
			if(addQty > 0){
				finalPrices = eval(finalPrices*getQty); 
			}
			return finalPrices;
		}

		function ceil(a) {
			return Math.ceil(a)
		}

		function floor(a) {
			return Math.floor(a)
		}


		function round(a) {
			return Math.round(a)
		}
	});