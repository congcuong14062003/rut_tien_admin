<?php
use Google\Auth\Credentials\ServiceAccountCredentials;
use Google\Auth\HttpHandler\HttpHandlerFactory;

require '../vendor/autoload.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'] ?? '';
    $title = $_POST['title'] ?? '';
    $body = $_POST['body'] ?? '';
    $image = $_POST['image'] ?? '';

    if (!empty($token) && !empty($title) && !empty($body)) {
        $credential = new ServiceAccountCredentials(
            "https://www.googleapis.com/auth/firebase.messaging",
            json_decode(file_get_contents("../pvKey.json"), true)
        );

        $authToken = $credential->fetchAuthToken(HttpHandlerFactory::build());

        $ch = curl_init("https://fcm.googleapis.com/v1/projects/push-notify-a24de/messages:send");

        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $authToken['access_token']
        ]);

        $postData = [
            "message" => [
                "token" => $token,
                "notification" => [
                    "title" => $title,
                    "body" => $body,
                    "image" => $image
                ],
                "webpush" => [
                    "fcm_options" => [
                        "link" => "https://push.citybank.club/admin/home"
                    ]
                ]
            ]
        ];

        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            error_log("cURL error: " . curl_error($ch));
        }
        curl_close($ch);

        echo $response;
    } else {
        echo 'Missing parameters';
    }
}
?>
