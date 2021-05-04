<?php

namespace Drupal\paypal_converter;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\commerce_exchanger\Exception\ExchangeRatesDataMismatchException;
use Drupal\commerce_price\Price;
use Drupal\commerce_price\RounderInterface;

class ConverterSettings {

    protected $currencyStorage;

    protected $rounder;

    public function __construct(EntityTypeManagerInterface $entity_type_manager, RounderInterface $rounder) {
        $this->currencyStorage = $entity_type_manager->getStorage('commerce_currency');
        $this->rounder = $rounder;
    }

    public function getAll($locale = NULL) {
        $all = [];
        $currencies = $this->currencyStorage->loadMultiple();
        return $currencies;
    }

    public function priceConversion(Price $price, string $target_currency) {
        $currencies = \Drupal::config('paypal_converter.commerce_price.currencies')->get('currencies');

        if (empty($currencies)) {
            throw new ExchangeRatesDataMismatchException('Not any active Exchange rates present');
        }

        // Price currency.
        $price_currency = $price->getCurrencyCode();

        // If someone is trying to convert same currency.
        if ($price_currency == $target_currency) {
            return $price;
        }

        $rate = $currencies[$price_currency][$target_currency] ?? 0;
        // Don't allow multiply with zero or one.
        if (empty($rate)) {
            throw new ExchangeRatesDataMismatchException('There are no exchange rates set for ' . $price_currency . ' and ' . $target_currency);
        }

        // Convert amount to target currency.
        $price = $price->convert($target_currency, (string) $rate);
        $price = $this->rounder->round($price);
        return $price;
    }

}