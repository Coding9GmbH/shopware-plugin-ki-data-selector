<?php

// Dummy API für Product Set Manager
// Diese Datei simuliert eine externe API, die Set-Informationen zurückgibt

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Hole den Set-Code aus den Query-Parametern
$setCode = $_GET['code'] ?? '';

// Dummy-Daten für verschiedene Set-Codes
$sets = [
    'BUNDLE001' => [
        'code' => 'BUNDLE001',
        'name' => 'Starter Bundle',
        'totalPrice' => 99.99,
        'components' => [
            [
                'productNumber' => 'SWDEMO10001',
                'quantity' => 2,
                'name' => 'Demo Product 1'
            ],
            [
                'productNumber' => 'SWDEMO10002',
                'quantity' => 1,
                'name' => 'Demo Product 2'
            ]
        ]
    ],
    'BUNDLE002' => [
        'code' => 'BUNDLE002',
        'name' => 'Premium Bundle',
        'totalPrice' => 199.99,
        'components' => [
            [
                'productNumber' => 'SW10178',
                'quantity' => 3,
                'name' => 'Shopware Logo T-Shirt'
            ],
            [
                'productNumber' => 'SW10179',
                'quantity' => 2,
                'name' => 'Shopware Mug'
            ],
            [
                'productNumber' => 'SW10180',
                'quantity' => 1,
                'name' => 'Shopware Hoodie'
            ]
        ]
    ],
    'BUNDLE003' => [
        'code' => 'BUNDLE003',
        'name' => 'Basic Bundle',
        'totalPrice' => 49.99,
        'components' => [
            [
                'productNumber' => 'SW10178',
                'quantity' => 1,
                'name' => 'Shopware Logo T-Shirt'
            ],
            [
                'productNumber' => 'SW10181',
                'quantity' => 1,
                'name' => 'Shopware Sticker Set'
            ]
        ]
    ]
];

// Prüfe ob Set-Code existiert
if (empty($setCode)) {
    http_response_code(400);
    echo json_encode(['error' => 'No set code provided']);
    exit;
}

if (!isset($sets[$setCode])) {
    http_response_code(404);
    echo json_encode(['error' => 'Set not found']);
    exit;
}

// Gebe Set-Daten zurück
echo json_encode($sets[$setCode]);