<?php

namespace kennethormandy\marketplace\variables;

use Craft;
use craft\base\Element;
use craft\helpers\ArrayHelper;
use craft\helpers\Template as TemplateHelper;
use kennethormandy\marketplace\Marketplace;
use Twig\Markup;
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
        $path = $this->_getTemplateBasePath() . 'hosted-onboarding';

        $html = Craft::$app->view->renderTemplate($path, [
            'elementUid' => $elementUid,
            'params' => $params,
            'themeConfig' => $themeConfig,
            'errorMessage' => $errorMessage,
        ]);

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
        $path = $this->_getTemplateBasePath() . 'hosted-dashboard';

        $html = Craft::$app->view->renderTemplate($path, [
            'elementUid' => $elementUid,
            'params' => $params,
            'themeConfig' => $themeConfig,
            'errorMessage' => $errorMessage,
        ]);

        return TemplateHelper::raw($html);
    }

    /**
     * Determine whether to use the plugin’s site template root, defined in the main plugin
     * file, or the plugin default template root for control panel templates like fields.
     */
    private function _getTemplateBasePath(): string
    {
        $isCp = Craft::$app->view->getTemplateMode() === 'cp';
        return $isCp ? 'marketplace/_site/' : 'marketplace/';
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
                ],
            ],
        ];

        return ArrayHelper::merge($themeConfigDefault, $themeConfig);
    }
}
