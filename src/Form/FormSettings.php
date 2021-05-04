<?php

namespace Drupal\paypal_converter\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;

class FormSettings extends ConfigFormBase {

    protected function getEditableConfigNames(): array {
        return [
            'paypal_converter.commerce_price.currencies',
        ];
    }

    public function getFormId() {
        return 'paypal_converter_commerce_price_currencies';
    }
    
    
    public function buildForm(array $form, FormStateInterface $form_state) {
        $config = $this->config('paypal_converter.commerce_price.currencies');
        
        // Load currencies.
        $currencies = \Drupal::service('paypal_converter.converter_settings')->getAll();
        $demo_amount = $plugin_configuration['demo_amount'] ?? 100;
        if (count($currencies) < 2) {
            $form['warning'] = [
              '#markup' => $this->t('Minimum of two currencies needs to be enabled, to be able to add exchange rates'),
            ];
            return $form;
        }
        
        $form['exchange_rates'] = [
            '#type' => 'details',
            '#title' => $this->t('Currency exchange rates'),
            '#open' => TRUE,
            '#tree' => TRUE,
        ];
        foreach ($currencies as $key => $currency) {
            $form['exchange_rates'][$key] = [
                '#type' => 'details',
                '#title' => $currency->label(),
                '#open' => FALSE,
            ];
            foreach ($currencies as $subkey => $subcurrency) {
                if ($key !== $subkey) {
                    $form['exchange_rates'][$key][$subkey]['value'] = [
                        '#type' => 'textfield',
                        '#title' => $subkey,
                        '#size' => 20,
                        '#default_value' => $config->get('currencies.'.$key . '.' . $subkey, ''),
                        '#field_suffix' => $this->t(' @currency_symbol => @conversion_currency_symbol', ['@currency_symbol' => $key, '@conversion_currency_symbol' => $subkey ]),
                    ];
                }
            }
        }
        $form['status'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Enabled'),
            '#default_value' => $config->get('status'),
        ];
        
        return parent::buildForm($form, $form_state);
    }

    public function submitForm(array &$form, FormStateInterface $form_state) {
        $data = $form_state->getValues();
        $currencies = [];
        foreach($data['exchange_rates'] as $key => $items) {
            $currencies[$key] = [];
            foreach($items as $currency => $item) {
                $currencies[$key][$currency] = $item['value'];
            }
        }      
        $this->config('paypal_converter.commerce_price.currencies')
                ->set('currencies', $currencies)
                ->set('status', $data['status'])
                ->save();

        parent::submitForm($form, $form_state);
    }

}
