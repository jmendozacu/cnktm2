/*browser:true*/
/*global define*/
/*define(
    [
        'Magento_Checkout/js/view/payment/default'
    ],
    function (Component) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Conekta_Oxxo/payment/oxxo'
            },

            *//** Returns send check to info *//*
            getMailingAddress: function() {
                return window.checkoutConfig.payment.checkmo.mailingAddress;
            },


        });
    }
);
*/




define(
    [
        'jquery',
        'Magento_Payment/js/view/payment/iframe',
        'Magento_Checkout/js/action/set-payment-information',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Checkout/js/model/full-screen-loader',

        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/url-builder',
        'mage/storage',
        'mage/url',
        'Magento_Checkout/js/model/error-processor',
        'Magento_Customer/js/model/customer',
        'Magento_Checkout/js/action/get-payment-information',

    ],
    function ($, Component, setPaymentInformationAction, additionalValidators, fullScreenLoader, quote,urlBuilder,storage,url,errorProcessor,customer, placeOrderAction) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'Conekta_Oxxo/payment/conekta_oxxo'
            },
            placeOrderHandler: null,
            validateHandler: null,

            setPlaceOrderHandler: function(handler) {
                this.placeOrderHandler = handler;
            },

            setValidateHandler: function(handler) {
                this.validateHandler = handler;
            },

            context: function() {
                return this;
            },

            isShowLegend: function() {
                return true;
            },

            getCode: function() {
                return 'conekta_oxxo';
            },
            
            isActive: function() {
                return true;
            },
            
            getPrivateKey: function() {
                return window.checkoutConfig.payment.conekta.privatekey;

            },
            
            
/*            placeOrder: function (data,paymentData) { 
                var self = this;
                var gettoken,status1;
               // if (this.validateHandler() && additionalValidators.validate()) {
         
                //alert(window.checkoutConfig.conekta.privatekey);
          
  
                 
                        var $form = $(this);
                 
                      //  var successResponseHandler = function(token,paymentData, redirectOnSuccess, messageContainer) {
                        
                        Conekta.charge.create({
                            "description":"Tomcook@rajneesh",
                            "amount": parseInt(quote.totals().base_grand_total)*100,
                            "currency":quote.totals().base_currency_code,
                            "reference_id":"9839-wolf_pack",
                            "cash": {
                                  "type": "oxxo",
                                  "expires_at": 1463296668
                                },
                          // 'monthly_installments'=> 3,
                            "details": {
                              "name": quote.billingAddress().firstname+ quote.billingAddress().lastname,
                              "phone": quote.billingAddress().telephone,
                              "email": 'test@gmail.com',
                              "customer": {
                                "logged_in": true,
                                "successful_purchases": 14,
                                "created_at": 1379784950,
                                "updated_at": 1379784950,
                                "offline_payments": 4,
                                "score": 9
                              },
                              "line_items": [{
                                "name": "Box of Cohiba S1s",
                                "description": "Imported From Mex.",
                                "unit_price": 20000,
                                "quantity": 1,
                                "sku": "cohb_s1",
                                "category": "food"
                              }],
                              "billing_address": {
                                "street1":quote.billingAddress().street,
                                "street2": quote.billingAddress().street,
                                "street3": null,
                                "city": quote.billingAddress().city,
                                "state":quote.billingAddress().region,
                                "zip": quote.billingAddress().postcode,
                                "country": quote.billingAddress().countryId,
                                "tax_id": "xmn671212drx",
                                "company_name":"X-Men Inc.",
                                "phone": quote.billingAddress().telephone,
                                "email": "purshasing@x-men.org"
                              }
                            }
                        }, function(res, err) {
                           
                             if(res.status=='pending_payment')
                                 {
                                     alert('order successful');    
                                     alert('Your Barcode Url is: '+res.payment_method.barcode_url);
                                    // alert(status1);
                                     status1=2;
                                     
                                     return true;
                                 }
                             else{
                                         alert(err.message);
                                         return false;
                                     }
                            // alert(res.message);
                              
                          });
                  
                  
                       //code end
                      if (event) {
                          event.preventDefault();
                      }
                     // alert(status1);
                      setTimeout(function(){ 
                      console.log(status1);
                      if (self.validate() && additionalValidators.validate()) {
                           console.log(status1);
                          if(status1==2){
                          self.isPlaceOrderActionAllowed(false);
                          var placeOrder = placeOrderAction(self.getData(), self.redirectAfterPlaceOrder, self.messageContainer);

                          $.when(placeOrder).fail(function () {
                              self.isPlaceOrderActionAllowed(true);
                          }).done(self.afterPlaceOrder.bind(self));
                          return true;
                          }
                          
                      }
                      }, 8000);
                      return false;
                      setTimeout(function(){ 
                          if(status1==2){
                              if (this.validateHandler() && additionalValidators.validate()) {
                                  fullScreenLoader.startLoader();
                                  this.isPlaceOrderActionAllowed(false);
                                  $.when(setPaymentInformationAction(this.messageContainer, {
                                      'method': self.getCode()
                                  })).done(function () {
                                      self.placeOrderHandler().fail(function () {
                                          fullScreenLoader.stopLoader();
                                      });
                                  }).fail(function () {
                                      fullScreenLoader.stopLoader();
                                      self.isPlaceOrderActionAllowed(true);
                                  });
                              }
                          }
                      }, 8000);
           
        }*/

           
        });
    }
);
