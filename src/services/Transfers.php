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
    private function _toStripeAmount($craftPrice, $currencyCountryCode)
    {
        $currency = Commerce::getInstance()->getCurrencies()->getCurrencyByIso($currencyCountryCode);
        
        if (!$currency) {
            throw new NotSupportedException('The currency “' . $currencyCountryCode . '” is not supported!');
        }

        // https://git.io/JGqLi
        // Ex. $50 * (10^2) = 5000
        $amount = $craftPrice * (10 ** $currency->minorUnit);

        $amount = (int) round($amount, 0);

        return $amount;
    }

    private function _fromStripeAmount($amount, $currencyCountryCode)
    {
        $currency = Commerce::getInstance()->getCurrencies()->getCurrencyByIso($currencyCountryCode);

        if (!$currency) {
            throw new NotSupportedException('The currency “' . $currencyCountryCode . '” is not supported!');
        }

        // https://git.io/JGqLi
        // Ex. 5000 / (10^2) = 50
        $craftPrice = $amount / (10 ** $currency->minorUnit);

        return $craftPrice;
    }
}
