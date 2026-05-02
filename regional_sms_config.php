<?php
/**
 * Regional (KE/UG) SMS provider configuration (EgoSMS Comms API).
 */
return array(
    "endpoints" => array(
        "https://comms.egosms.co/api/v1/json/"
    ),
    "username" => getenv("REGIONAL_SMS_USERNAME") ?: "",
    "password" => getenv("REGIONAL_SMS_PASSWORD") ?: "",
    "priority" => "0",
);
