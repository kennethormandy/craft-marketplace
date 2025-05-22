<?php

namespace kennethormandy\marketplace\services;

use Craft;
use craft\base\Component;
use craft\base\Element;
use craft\commerce\Plugin as Commerce;
use craft\commerce\elements\Order;
use Stripe\BalanceTransaction;
use Stripe\Stripe;
use Stripe\StripeClient;
use Stripe\Transfer;
use kennethormandy\marketplace\Marketplace;
use craft\helpers\Console as ConsoleHelper;

/**
 * Transfers service
 */
class Transfers extends Component
{
    private $_stripe = null;

    /**
     * @var StripeClient|null
     */
    private ?StripeClient $_client = null;

    /**
     * Create the separate charges and transfers for an existing order.
     */
    function createTransfersForOrder(Order $order, $throw = false): void
    {

        $plugin = Marketplace::getInstance();
        $purchaseTransaction = null;

        foreach ($order->transactions as $transaction) {
            // Stop at the first successful transaction, can also be failed
            // TODO Does auth and capture still create this?
            if ($transaction->type === 'purchase' && $transaction->status === 'success') {

                /** @var craft\commerce\models\Transaction $purchaseTransaction */
                $purchaseTransaction = $transaction;
                break;
            }
        }

        if (!$purchaseTransaction || !$purchaseTransaction->reference) {
            $plugin->log('No purchase transaction found on Order ' . $order->id, [], 'error');
            return;
        }

        $stripeResp = json_decode($purchaseTransaction->response);
        $currencyCountryCode = $purchaseTransaction->paymentCurrency;

        $plugin->log('Original transaction currency: ' . $currencyCountryCode);
        $plugin->log('Transaction:');
        $plugin->log(json_encode($purchaseTransaction));


        $stripeCharge = null;

        if ($stripeResp->latest_charge) {
            $stripe = $this->_getClient();
            $stripeCharge = $stripe->charges->retrieve($stripeResp->latest_charge);
        }

        // TODO If we don’t have it, get all Stripe charges using:
        // https://docs.stripe.com/api/charges/list
        // Providing the payment intent

        // // Get the first captured transaction
        // if (
        //     isset($stripeResp->charges) && $stripeResp->charges &&
        //     isset($stripeResp->charges->data) && $stripeResp->charges->data &&
        //     count($stripeResp->charges->data) >= 1
        // ) {
        //     foreach ($stripeResp->charges->data as $charge) {
        //         $plugin->log(json_encode($charge));
        //         if ($charge && $charge->captured && $charge->status === 'succeeded') {
        //             $stripeCharge = $charge;
        //             break;
        //         }
        //     }
        // }

        if (!$stripeCharge) {
            $plugin->log('No successful charge found on Order ' . $order->id, [], 'error');
            return;
        }

        $plugin->log('Charge:');
        $plugin->log(json_encode($stripeCharge));

        // If a transfer group is already present, something’s gone
        // wrong—ex. you are running this as console command against an
        // order where transfers were already made.
        if ($stripeCharge->transfer_group) {
            $existingTransferGroup = $stripe->transfers->all([
                'transfer_group' => $stripeCharge->transfer_group
            ]);

            if ($existingTransferGroup) {
                $plugin->log($existingTransferGroup);
                $plugin->log($existingTransferGroup->data);

                if ($throw) {
                    throw new \Exception('This transaction already has existing transfers.', 1);
                }
            }

            // We don’t want to make addtional transfers without knowing why
            // some are already there.
            return;
        }

        try {
            $balanceTransaction = BalanceTransaction::retrieve($stripeCharge->balance_transaction);
            $plugin->log('Balance transaction:');
            $plugin->log(json_encode($balanceTransaction));
        } catch (\Exception $e) {
            $plugin->log('Marketplace transfer error', [], 'error');
            $plugin->log($e->getTraceAsString(), [], 'error');

            if ($throw) {
                throw new \Exception($e, 1);
            }
        }

        $exchangeRate = $this->_getStripeExchangeRate($balanceTransaction, $currencyCountryCode);

        foreach ($order->lineItems as $key => $lineItem) {
            $payeeCurrent = $plugin->payees->getAccountId($lineItem);

            $plugin->log('lineItem');
            $plugin->log(json_encode($lineItem));

            // If there isn’t a payee or a total on this line item, nothing to do
            if (!$payeeCurrent || $lineItem->total === (float) 0) {
                continue;
            }

            $plugin->log('Craft amount before currency conversion: ' . $lineItem->total);

            $lineItemTotal = $lineItem->total;

            // Calculate LineItem fee
            $feeAmountLineItem = $plugin->fees->calculateFeesAmount($lineItem, $order);

            if ($feeAmountLineItem) {
                $lineItemTotal = $lineItemTotal - $feeAmountLineItem;
            }

            // Don’t touch the subtotal, unless we really to have to
            if ($exchangeRate && $exchangeRate !== 1) {
                $lineItemTotal = $lineItem->total * $exchangeRate;
            }

            $plugin->log('Craft amount after currency conversion: ' . $lineItemTotal);

            $stripeAmount = $this->_toStripeAmount($lineItemTotal, $currencyCountryCode);
            $plugin->log('Stripe amount: ' . $stripeAmount);
            
            $plugin->log('In progress: Create transfer for ' . $payeeCurrent);

            $stripeTransferData = [
                'amount' => $stripeAmount,

                // Have to use the balance transaction currency
                // Ex. If the platform is using GBP (the settlement
                // currency), and the customer purchased using USD (the
                // presettlement currency), the balance transaction
                // and future payout will be in GBP, and therefore the
                // transfer has to be in GBP as well.
                'currency' => $balanceTransaction->currency,

                'destination' => $payeeCurrent,

                // Don’t need to create a `transfer_group`, Stripe
                // does this via the source_transaction
                'source_transaction' => $stripeCharge->id,
            ];

            try {
                $transferResult = Transfer::create($stripeTransferData);
                $plugin->log('Transfer Result');
                $plugin->log(json_encode($transferResult));
            } catch (\Exception $e) {
                $plugin->log('Marketplace transfer error', [], 'error');
                $plugin->log($e->getTraceAsString(), [], 'error');

                if ($throw) {
                    throw new \Exception($e, 1);
                }
            }
        }

    }

    /**
     * Returns or sets up a StripeClient.
     *
     * @return StripeClient
     * @see https://github.com/craftcms/stripe/blob/1.x/src/services/Api.php
     * @license https://github.com/craftcms/stripe/blob/1.x/LICENSE.md
     */
    public function _getClient(): StripeClient
    {
        if ($this->_client === null) {

            $apiKey = Marketplace::getInstance()->getSettings()->getSecretApiKey();

            // Stripe::setAppInfo(Marketplace::getInstance()->name, Marketplace::getInstance()->version, Marketplace::getInstance()->documentationUrl);
            Stripe::setApiKey($apiKey);
            // Stripe::setApiVersion(self::STRIPE_API_VERSION);
            // Stripe::setMaxNetworkRetries(3);
            // Stripe::setLogger($webLogTarget->getLogger());

            $this->_client = new StripeClient([
                'api_key' => $apiKey,
                // 'stripe_version' => self::STRIPE_API_VERSION,
            ]);
        }

        return $this->_client;
    }

    private function _getStripeExchangeRate($stripeBalanceTransaction, $craftCurrencyCountryCode)
    {
        $exchangeRate = 1;

        if (
            strtolower($craftCurrencyCountryCode) !== strtolower($stripeBalanceTransaction) &&
            $stripeBalanceTransaction->exchange_rate
        ) {
            // $this->log('Need to convert currency');
            $exchangeRate = $stripeBalanceTransaction->exchange_rate;
        }

        return $exchangeRate;
    }

    // TODO Move to service, ex. ConvertService?
    /**
     * Normalize from Craft format into Stripe format, ex. $50 * (10^2) = 5000
     *
     * @param float $craftPrice The amount in Craft’s format, ex. 50.00
     * @param string $currencyCountryCode The ISO country code for the transaction
     * @return int The amount in Stripe’s format, ex. 5000
     */
    private function _toStripeAmount(float $craftPrice, string $currencyCountryCode): int
    {
        $currencyService = Commerce::getInstance()->getCurrencies();
        $currency = $currencyService->getCurrencyByIso($currencyCountryCode);
        
        if (!$currency) {
            throw new NotSupportedException('The currency “' . $currencyCountryCode . '” is not supported!');
        }

        /** @see https://github.com/craftcms/commerce-stripe/blob/bcfa0d7ee930a4710c0d43ae69830e135aa3a7c7/src/gateways/PaymentIntents.php#L308 */
        $amount = (int) bcmul($craftPrice, 10 ** $currencyService->getSubunitFor($currency));

        $amount = (int) round($amount, 0);

        return $amount;
    }

    /**
     * Normalize from Stripe format into Craft format, ex. 5000 / (10^2) = 50
     *
     * @param int $amount The amount in Stripe format, ex. 5000
     * @param string $currencyCountryCode The ISO country code for the transaction
     * @return float The amount in Stripe format, ex. 50.00
     */
    private function _fromStripeAmount(int $amount, string $currencyCountryCode): float
    {
        $currency = Commerce::getInstance()->getCurrencies()->getCurrencyByIso($currencyCountryCode);

        if (!$currency) {
            throw new NotSupportedException('The currency “' . $currencyCountryCode . '” is not supported!');
        }

        /** @see https://github.com/craftcms/commerce-stripe/blob/bcfa0d7ee930a4710c0d43ae69830e135aa3a7c7/src/gateways/PaymentIntents.php#L308 */
        $craftPrice = (float) bcmul($amount / (10 ** $currencyService->getSubunitFor($currency)));

        return $craftPrice;
    }
}
