<?php
/**
 * FastHub integration switchboard (Tanzania 255… routes in smpp_client.php).
 *
 * On the FastHub bulk portal the approved sender name is "VLL SMS" (space).
 * Legacy "VLL-SMS" is normalised to "VLL SMS" in phone_lib / workers — keep
 * FastHub registration aligned with that string or sends may be rejected.
 *
 * Change values here only; no need to edit smpp_client.php.
 */
return array(
    // Modes: auto, auth_messages_text, auth_messages_mobile, auth_messages_camel_text, flat_credentials
    "payload_mode" => "auth_messages_camel_text",

    // Try these endpoints in order when mode is auto (or when first endpoint fails).
    "endpoints" => array(
        "https://bulksms.fasthub.co.tz/api/sms/send"
    ),

    // Auth credentials provided by FastHub.
    "client_id" => "85f3fb5b-34c8-44dc-bb6f-429b59328229",
    "client_secret" => "3c35d5cd-49df-4ea6-8709-6d8437f172d3",
);
