<?php

putenv('GOOGLE_APPLICATION_CREDENTIALS=/Users/sbca/Documents/keys/smartyardoem-e32c74c169ec.json');

require_once 'vendor/autoload.php'; //-- loading the google api client

$client = new Google_Client();
$client->useApplicationDefaultCredentials();
$client->addScope('https://www.googleapis.com/auth/firebase.messaging');
$httpClient = $client->authorize();


// Your Firebase project ID
$project = "smartyardoem";
// Creates a notification for subscribers to the debug topic
$message = [
    "message" => [
        "token" => "fLozFwX1EkFzr1q8BCbbq0:APA91bG6R_3eL36u4Ym9Axb7td3kB2EPIQcru2eZQFVfpl67L2TaWrW8uyyOSThnKessJv7watdGbRgcf4dNQeAbHNTmW9DSg98vu3iVbmt5wuj_7aCvCywJTLHP4utpgRds2qSTMlgV",
        "notification" => [
            "body" => "This is an FCM notification message!",
            "title" => "FCM Message",
        ]
    ]
];
// Send the Push Notification - use $response to inspect success or errors
$response = $httpClient->post("https://fcm.googleapis.com/v1/projects/{$project}/messages:send", ['json' => $message]);

var_dump($response);