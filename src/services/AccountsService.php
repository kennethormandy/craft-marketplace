<?php

namespace kennethormandy\marketplace\services;

use Craft;
use craft\base\Component;
use kennethormandy\marketplace\Marketplace;
use Stripe\Account as StripeAccount;
use Stripe\Stripe;

class AccountsService extends Component
{
    public function init()
    {
        parent::init();
    }

    public function createLoginLink($accountId = null, $params = [])
    {
        if (empty($accountId) || !$accountId) {
            return null;
        }

        $link = null;

        // $plugin->getSettings didn’t allow me to test?
        $secretApiKey = Marketplace::$plugin->settings->getSecretApiKey();

        if (!isset($secretApiKey) || !$secretApiKey) {
            return 'Missing API key';
        }

        // TODO Should this be done once at the plugin level?
        try {
            Stripe::setApiKey($secretApiKey);
        } catch (Exception $e) {
            // TODO
            return json_encode($e);
        }

        try {
            $resp = StripeAccount::createLoginLink($accountId, $params);
            if ($resp->url) {
                $link = $resp->url;
            }
        } catch (Exception $e) {
            // TODO
            return json_encode($e);
        }

        return $link;
    }
}
