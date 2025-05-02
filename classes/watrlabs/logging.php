<?php

namespace watrlabs;

class logging {
    public function __construct() {
        return "Hello World!";
    }
    
    public function helloworld() {
        return "Hello World!";
    }

    public function errorwebhook($error) {
        // just steal- i mean borrowing code from https://gist.github.com/Mo45/cb0813cb8a6ebcd6524f6a36d4f8862c

        $timestamp = date("c", strtotime("now"));
        $json_data = json_encode([
            "tts" => false,
            "embeds" => [
                [
                    "title" => "Website error occured",
                    "type" => "rich",
                    "description" => "$error>",
                    "timestamp" => $timestamp,
                    "color" => hexdec( "007182" ),
                ]
            ]

        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );


        $ch = curl_init( prodwebhook );
        curl_setopt( $ch, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
        curl_setopt( $ch, CURLOPT_POST, 1);
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $json_data);
        curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt( $ch, CURLOPT_HEADER, 0);
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1);

        $response = curl_exec( $ch );
        // If you need to debug, or find out why you can't send message uncomment line below, and execute script.
        // echo $response;
        curl_close( $ch );

    }

    public function logwebhook($log) {
        // just steal- i mean borrowing code from https://gist.github.com/Mo45/cb0813cb8a6ebcd6524f6a36d4f8862c

        //include(baseurl . "/config.php");   
        $timestamp = date("c", strtotime("now"));
        $json_data = json_encode([
            "tts" => false,
            "embeds" => [
                [
                    "title" => "watrbx - site log", // i fucking hard coded it because php's a piece of shit
                    "type" => "rich",
                    "description" => "htmlspecialchars($log)",
                    "timestamp" => $timestamp,
                    "color" => hexdec( "007182" ),
                ]
            ]

        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );


        $ch = curl_init( prodwebhookurl );
        curl_setopt( $ch, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
        curl_setopt( $ch, CURLOPT_POST, 1);
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $json_data);
        curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt( $ch, CURLOPT_HEADER, 0);
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1);

        $response = curl_exec( $ch );
        // If you need to debug, or find out why you can't send message uncomment line below, and execute script.
        // echo $response;
        curl_close( $ch );

    }
}
