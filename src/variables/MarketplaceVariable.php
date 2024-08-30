<?php

namespace kennethormandy\marketplace\variables;

use Craft;
use craft\base\Element;
use craft\helpers\ArrayHelper;
use craft\helpers\Template as TemplateHelper;
use kennethormandy\marketplace\Marketplace;
use Twig\Markup;
use verbb\auth\Auth;
use verbb\auth\helpers\Session;

class MarketplaceVariable
{
    /**
     * @param $elementRef - An element or element UID that could be used as an account, falling back to the current user.
     */
    public function renderConnector(Element|string|null $elementRef = null, array $themeConfig = [], array $params = [
        'redirect' => null,
        'referrer' => null,
    ]): ?Markup
    {
        $isConnected = Marketplace::$plugin->accounts->isConnected($elementRef);

        if ($isConnected) {
            return $this->renderHostedDashboard($elementRef, $themeConfig, $params);
        }
        
        return $this->renderHostedOnboarding($elementRef, $themeConfig, $params);
    }

    public function renderHostedOnboarding(Element|string|null $elementRef = null, array $themeConfig = [], array $params = [
        'redirect' => null,
        'referrer' => null,
    ]): ?Markup
    {
        $elementUid = $elementRef->uid ?? $elementRef;
        $errorMessage = Session::getError('marketplace');
        $themeConfig = $this->_getThemeConfig($themeConfig);

        $html = Craft::$app->view->renderTemplate(
            'marketplace/hosted-onboarding',
            [
                'elementUid' => $elementUid,
                'params' => $params,
                'themeConfig' => $themeConfig,
                'errorMessage' => $errorMessage,
            ]
        );

        return TemplateHelper::raw($html); 
    }

    // This is pretty much what the example is, but thinking it should become
    // - `renderHostedDashboard` - Explicitly render the dashboard button
    // - `renderHostedOnboarding` - Explicitly render the onboarding button
    // - `renderConnector` or `renderConnection` - Render the onboarding if they aren’t connected, and render the dashboard if they are (a simple wrapper of the other two functions)
    //   that will be what most people want—show the onboarding or dashboard button in the same place
    public function renderHostedDashboard(Element|string|null $elementRef = null, array $themeConfig = [], array $params = [
        'redirect' => null,
        'referrer' => null,
    ]): ?Markup
    {
        $elementUid = $elementRef->uid ?? $elementRef;
        $errorMessage = Session::getError('marketplace');
        $themeConfig = $this->_getThemeConfig($themeConfig);

        $html = Craft::$app->view->renderTemplate(
            'marketplace/hosted-dashboard',
            [
                'elementUid' => $elementUid,
                'params' => $params,
                'themeConfig' => $themeConfig,
                'errorMessage' => $errorMessage,
            ]
        );

        return TemplateHelper::raw($html);
    }

    private function _getThemeConfig(array $themeConfig = []): array
    {
        $themeConfigDefault = [
            'form' => [
                'attributes' => [
                    'class' => '',
                ],
            ],
            'submitButton' => [
                'attributes' => [
                    'class' => '',
                ]
            ],
        ];

        return ArrayHelper::merge($themeConfigDefault, $themeConfig);
    }
}
