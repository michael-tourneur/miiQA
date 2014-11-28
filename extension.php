<?php

return [

    'main' => 'Mii\\Qa\\MiiQaExtension',

    'autoload' => [

        'Mii\\Qa\\' => 'src'

    ],

    'resources' => [

        'export' => [
            'view' => 'views',
            'asset' => 'assets'
        ]

    ],

    'controllers' => 'src/Controller/*Controller.php',

    'menu' => [

        'miiQA' => [
            'label'  => 'miiQA',
            'icon'   => 'extension://miiqa/extension.png',
            'url'    => '@miiQA/admin/question',
            'active' => '@miiQA/admin/question*',
            'access' => 'miiQA: manage questions || miiQA: manage answers',
            'priority' => 0
        ],
        'miiQA: question list' => [
            'label'  => 'Questions',
            'parent' => 'miiQA',
            'url'    => '@miiQA/admin/question',
            'active' => '@miiQA/admin/question*',
            'access' => 'miiQA: manage questions'
        ],
        'miiQA: answer list' => [
            'label'  => 'Answers',
            'parent' => 'miiQA',
            'url'    => '@miiQA/admin/answer',
            'active' => '@miiQA/admin/answer*',
            'access' => 'miiQA: manage answers'
        ],
        'miiQA: tag list' => [
            'label'  => 'Tags',
            'parent' => 'miiQA',
            'url'    => '@miiQA/admin/tag',
            'active' => '@miiQA/admin/tag*',
            'access' => 'miiQA: manage tags'
        ],

    ],

    'permissions' => [

        'miiQA: manage settings' => [
            'title' => 'Manage settings'
        ],
        'miiQA: manage questions' => [
            'title' => 'Manage questions'
        ],
        'miiQA: manage answers' => [
            'title' => 'Manage answers'
        ],
        'miiQA: manage tags' => [
            'title' => 'Manage tags'
        ]

    ],


    'defaults' => [

        'index_items_per_page'  => 20,

    ]


];
