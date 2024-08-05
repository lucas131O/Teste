<?php

namespace MestreMage\OneStepCheckout\Plugin\Checkout\Block\Checkout;

use \Magento\Store\Model\ScopeInterface;

class LayoutProcessor
{


    public function __construct(
        \Magento\Customer\Block\Widget\Gender $gender,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfigInterface
    ) {
        $this->gender = $gender;
        $this->scopeConfigInterface = $scopeConfigInterface;
    }

	public function afterProcess($subject, $jsLayout)
    {
        $gender_show = $this->scopeConfigInterface->getValue('customer/address/gender_show',ScopeInterface::SCOPE_STORES);
        $dob_show = $this->scopeConfigInterface->getValue('customer/address/dob_show',ScopeInterface::SCOPE_STORES);

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        if ($objectManager->get('MestreMage\OneStepCheckout\Helper\Config')->isEnabledOneStep()) {
            if (isset($jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children'])) {
                if(isset($jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['afterMethods']['children']['billing-address-form']))
                {
                    $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['afterMethods']['children']['billing-address-form']['component'] = 'MestreMage_OneStepCheckout/js/view/billing-address';
                }

                if (!$objectManager->create('Magento\Customer\Model\Session')->isLoggedIn()) {

                    $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']
                    ['shippingAddress']['children']['shipping-address-fieldset']['children']['code-password'] = [
                        'component' => 'Magento_Ui/js/form/element/abstract',
                        'label' => __('Password'),
                        'config' => [
                            'customScope' => 'shippingAddress',
                            'template' => 'ui/form/field',
                            'elementTmpl' => 'ui/form/element/password',
                        ],
                        'provider' => 'checkoutProvider',
                        'dataScope' => 'code-password',
                        'sortOrder' => 0,
                    ];



                    $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']
                    ['shippingAddress']['children']['shipping-address-fieldset']['children']['code-confirm_password'] = [
                        'component' => 'Magento_Ui/js/form/element/abstract',
                        'label' => __('Confirm Password'),
                        'config' => [
                            'customScope' => 'shippingAddress',
                            'template' => 'ui/form/field',
                            'elementTmpl' => 'ui/form/element/password',
                        ],
                        'provider' => 'checkoutProvider',
                        'dataScope' => 'code-confirm_password',
                        'sortOrder' => 1,
                    ];
                }


                if ($gender_show) {
                    $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']
                    ['shippingAddress']['children']['shipping-address-fieldset']['children']['mm_gender'] = [
                        'component' => 'Magento_Ui/js/form/element/select',
                        'config' => [
                            'customScope' => 'shippingAddress',
                            'template' => 'ui/form/field',
                            'elementTmpl' => 'ui/form/element/select'
                        ],
                        'dataScope' => 'shippingAddress.mm_gender',
                        'label' => __('Gender'),
                        'provider' => 'checkoutProvider',
                        'visible' => true,
                        'sortOrder' => 50,
                        'validation' => ['required-entry' => ($gender_show == 'req' ? true : false)],
                        'options' => $this->getGenderOptions()
    
                    ];

                }


                if ($dob_show) {
                    $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']
                    ['shippingAddress']['children']['shipping-address-fieldset']['children']['mm_dob'] = [
                        'component' => 'Magento_Ui/js/form/element/date',
                        'label' => __('Date of Birth'),
                        'config' => [
                            'customScope' => 'shippingAddress',
                            'template' => 'ui/form/field',
                            'elementTmpl' => 'ui/form/element/date',
                        ],
                        'provider' => 'checkoutProvider',
                        'dataScope' => 'shippingAddress.mm_dob',
                        'sortOrder' => 50,
                        'validation' => ['required-entry' => ($dob_show == 'req' ? true : false)],
                    ];

                }

            }
        }
        return $jsLayout;
    }

    function getGenderOptions(){
        $return = [];
        foreach ($this->gender->getGenderOptions() as $key => $option) {
            $return[$key]['value'] = $option->getValue();
            $return[$key]['label'] = __($option->getLabel());
        }
        return $return;
    }
}