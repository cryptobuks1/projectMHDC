<?php

return [
  'gcm' => [
      'priority' => 'high',
      'dry_run' => false,
      'apiKey' => 'My_ApiKey',
  ],
  'fcm' => [
        'priority' => 'high',
        'dry_run' => false,
        'apiKey' => env('FCM_KEY'),
  ],
  'apn' => [
      'certificate' => __DIR__ . '/iosCertificates/cert_voip.pem',
      'passPhrase' => '123456@', //Optional
      //'passFile' => __DIR__ . '/iosCertificates/yourKey.pem', //Optional

      'dry_run' => false,
      'production' => true
  ]
];