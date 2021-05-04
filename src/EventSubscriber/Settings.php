<?php

namespace Drupal\paypal_converter\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\commerce_paypal\Event\ExpressCheckoutRequestEvent;
use Drupal\commerce_price\Price;
use Drupal\commerce_exchanger\ExchangerCalculatorInterface;

/**
 * Class EntityTypeSubscriber.
 *
 * @package Drupal\paypal_converter\EventSubscriber
 */
class Settings implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [
      'commerce_paypal.express_checkout_request' => ['expressCheckout', 0],
    ];
    return $events;
  }

  /**
   * {@inheritdoc}
   */
  public function expressCheckout(ExpressCheckoutRequestEvent &$event) {
      $data = $event->getNvpData();
      $currency = $data['PAYMENTREQUEST_0_CURRENCYCODE'];
      $price = $this->price_conversion($data['PAYMENTREQUEST_0_AMT'], $currency);
      $order = $event->getOrder();
      $i = 0;
      foreach ($order->getItems() as $item) {
        $data['L_PAYMENTREQUEST_0_AMT' . $i] = $this->price_conversion($data['L_PAYMENTREQUEST_0_AMT' . $i], $currency);
        $i++;
      }
      if(!empty($promontion_status)) {
        $data['L_PAYMENTREQUEST_0_AMT' . $i] = $this->price_conversion($data['L_PAYMENTREQUEST_0_AMT' . $i], $currency);
      }
      $data['PAYMENTREQUEST_0_CURRENCYCODE'] = "USD";
      $data['PAYMENTREQUEST_0_AMT'] = $this->price_conversion($data['PAYMENTREQUEST_0_AMT'], $currency);
      $data['PAYMENTREQUEST_0_SHIPPINGAMT'] = $this->price_conversion($data['PAYMENTREQUEST_0_SHIPPINGAMT'], $currency);
      $data['PAYMENTREQUEST_0_ITEMAMT'] = $this->price_conversion($data['PAYMENTREQUEST_0_ITEMAMT'], $currency);  
      $event->setNvpData($data);
  }

  /**
   * {@inheritdoc}
   */
  public function price_conversion($amount, $currency) {
      if(!is_numeric($amount)) {
        return;
      }
      $price = \Drupal::service('paypal_converter.converter_settings')->priceConversion(new Price($amount, $currency), 'USD')->getNumber();
      return $price;
  }

}