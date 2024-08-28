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
        $elementUid = $elementRef->uid ?? $elementRef;
        $errorMessage = Session::getError('marketplace');

        $themeConfigDefault = [
            'form' => [
                'attributes' => [
                    'class' => '',
                ],
            ],
            'submitButton' => [
                'label' => 'Open Dashboard',
                'attributes' => [
                    'class' => '',
                ]
            ],
        ];

        $themeConfig = ArrayHelper::merge($themeConfigDefault, $themeConfig);


        $html = Craft::$app->view->renderTemplate(
            'marketplace/example',
            [
                'elementUid' => $elementUid,
                'params' => $params,
                'themeConfig' => $themeConfig,
                'errorMessage' => $errorMessage,
            ]
        );

        return TemplateHelper::raw($html);
    }
}
