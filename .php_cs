<?php
use yii\cs\YiisoftConfig;

$header = <<<'HEADER'
@link https://kennethormandy.com
@copyright Copyright © 2019–2021 Kenneth Ormandy Inc.
@license https://github.com/kennethormandy/craft-marketplace/blob/main/LICENSE.md
HEADER;

return YiisoftConfig::create()
    ->mergeRules([
        // Overwrite the default Yii2 header rule.
        // Also not inserting my own header, either.
        'header_comment' => false,
    ])
    ->setFinder(
        PhpCsFixer\Finder::create()
            ->in('src')
            ->in('tests/codeception/acceptance')
            ->in('tests/codeception/functional')
            ->in('tests/codeception/unit')
    );
