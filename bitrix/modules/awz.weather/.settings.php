<?php
return [
    'ui.entity-selector' => [
        'value' => [
            'entities' => [
                [
                    'entityId' => 'awzweather-user',
                    'provider' => [
                        'moduleId' => 'awz.weather',
                        'className' => '\\Awz\\Weather\\Access\\EntitySelectors\\User'
                    ],
                ],
                [
                    'entityId' => 'awzweather-group',
                    'provider' => [
                        'moduleId' => 'awz.weather',
                        'className' => '\\Awz\\Weather\\Access\\EntitySelectors\\Group'
                    ],
                ],
            ]
        ],
        'readonly' => true,
    ]
];