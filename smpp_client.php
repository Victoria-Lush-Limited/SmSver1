<?php
include "db/dblink.php";
include __DIR__ . "/phone_lib.php";

error_reporting(0);
ini_set("display_errors", "0");

// Provider routing config:
// - Tanzania (255…) -> FastHub
// - Kenya (254…), Uganda (256…) -> Regional provider
$fasthub = include "fasthub_config.php";
$regional = include "regional_sms_config.php";

$providers = array(
    "FastHub" => array(
        "prefixes" => array("255"),
        "config" => $fasthub
    ),
    "Regional" => array(
        "prefixes" => array("254", "256"),
        "config" => $regional
    )
);
$max_attempts = (int) vll_env("VLL_OUTGOING_MAX_ATTEMPTS", "5");
if ($max_attempts < 1) {
    $max_attempts = 5;
}
$worker_batch = (int) vll_env("VLL_OUTGOING_WORKER_BATCH", "200");
if ($worker_batch < 30) {
    $worker_batch = 30;
}
if ($worker_batch > 500) {
    $worker_batch = 500;
}

$now = time();
$q = mysqli_query(
    $conn,
    "SELECT * FROM outgoing WHERE sms_status='Pending' AND date_created <= '" . intval($now) . "' AND attempts<'" . intval($max_attempts) . "' ORDER BY sms_id ASC LIMIT " . intval($worker_batch)
);

$routed = array();
function detect_provider_name($msisdn, $providers)
{
    foreach ($providers as $provider_name => $provider_info) {
        $prefixes = isset($provider_info["prefixes"]) ? $provider_info["prefixes"] : array();
        foreach ($prefixes as $prefix) {
            if (strpos($msisdn, $prefix) === 0) {
                return $provider_name;
            }
        }
    }
    return "";
}

function post_json($url, $payload)
{
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt_array($ch, array(
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => array('Content-Type: application/json'),
        CURLOPT_POSTFIELDS => json_encode($payload)
    ));
    $response = curl_exec($ch);
    $error = null;
    if ($response === false) {
        $error = curl_error($ch);
    }
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return array($response, $http_code, $error);
}

function provider_send_batch($conn, $provider_name, $provider_config, $messages, $now)
{
    $api_endpoints = isset($provider_config["endpoints"]) && is_array($provider_config["endpoints"]) ? $provider_config["endpoints"] : array();

    if (count($api_endpoints) === 0) {
        return array(false, 0, $provider_name . " config has no endpoints", "Rejected");
    }

    if ($provider_name === "Regional") {
        $username = isset($provider_config["username"]) ? trim($provider_config["username"]) : "";
        $password = isset($provider_config["password"]) ? trim($provider_config["password"]) : "";
        $priority = isset($provider_config["priority"]) ? (string)$provider_config["priority"] : "0";
        if ($username === "" || $password === "") {
            return array(false, 0, "Regional provider credentials missing", "Rejected");
        }

        $msgdata = array();
        foreach ($messages as $msg) {
            $msgdata[] = array(
                "number" => $msg["msisdn"],
                "message" => $msg["text"],
                "senderid" => $msg["source"],
                "priority" => $priority
            );
        }

        $payload = array(
            "method" => "SendSms",
            "userdata" => array(
                "username" => $username,
                "password" => $password
            ),
            "msgdata" => $msgdata
        );

        $api_url = $api_endpoints[0];
        list($response, $http_code, $curl_error) = post_json($api_url, $payload);
        $final_http = intval($http_code);
        $request_json = mysqli_real_escape_string($conn, json_encode($payload));
        $response_body = $curl_error ? $curl_error : (string)$response;
        $response_escaped = mysqli_real_escape_string($conn, $response_body);
        $provider_tag = "Regional[" . $api_url . "|SendSms]";

        if ($curl_error) {
            mysqli_query($conn, "INSERT INTO sms_api_logs(provider,request_body,response_body,http_code,status,created_at) VALUES('" . mysqli_real_escape_string($conn, $provider_tag) . "','" . $request_json . "','" . $response_escaped . "',0,'TransportError','" . $now . "')");
            return array(false, 0, "Transport error: " . substr($curl_error, 0, 200), "TransportError");
        }

        $json_data = json_decode($response);
        $is_ok = false;
        $message_text = "Regional rejected";
        if (is_object($json_data)) {
            if (isset($json_data->Status) && strtoupper((string)$json_data->Status) === "OK") {
                $is_ok = true;
            }
            if (isset($json_data->Message) && trim((string)$json_data->Message) !== "") {
                $message_text = trim((string)$json_data->Message);
            }
        } elseif (!empty($response)) {
            $message_text = substr(trim(strip_tags((string)$response)), 0, 220);
        }

        if ($final_http >= 200 && $final_http < 300 && $is_ok) {
            mysqli_query($conn, "INSERT INTO sms_api_logs(provider,request_body,response_body,http_code,status,created_at) VALUES('" . mysqli_real_escape_string($conn, $provider_tag) . "','" . $request_json . "','" . $response_escaped . "','" . $final_http . "','Success','" . $now . "')");
            return array(true, $final_http, "Regional accepted: " . $message_text, "Success");
        }

        $status = ($final_http >= 500 || $final_http == 0) ? "Retrying" : "Rejected";
        mysqli_query($conn, "INSERT INTO sms_api_logs(provider,request_body,response_body,http_code,status,created_at) VALUES('" . mysqli_real_escape_string($conn, $provider_tag) . "','" . $request_json . "','" . $response_escaped . "','" . $final_http . "','" . mysqli_real_escape_string($conn, $status) . "','" . $now . "')");
        return array(false, $final_http, $message_text, $status);
    }

    $client_id = isset($provider_config["client_id"]) ? trim($provider_config["client_id"]) : "";
    $client_secret = isset($provider_config["client_secret"]) ? trim($provider_config["client_secret"]) : "";
    $payload_mode = isset($provider_config["payload_mode"]) ? $provider_config["payload_mode"] : "auto";

    $messages_alt = array();
    foreach ($messages as $msg) {
        $messages_alt[] = array(
            "message" => $msg["text"],
            "mobile" => $msg["msisdn"],
            "msisdn" => $msg["msisdn"],
            "recipient" => $msg["msisdn"],
            "sender" => $msg["source"],
            "sender_id" => $msg["source"],
            "source" => $msg["source"],
            "reference" => $msg["reference"]
        );
    }

    $payload_variants = array(
        array(
            "name" => "auth_messages_camel_text",
            "payload" => array(
                "auth" => array("clientId" => $client_id, "clientSecret" => $client_secret),
                "messages" => $messages
            )
        ),
        array(
            "name" => "auth_messages_text",
            "payload" => array(
                "auth" => array("client_id" => $client_id, "client_secret" => $client_secret),
                "messages" => $messages
            )
        ),
        array(
            "name" => "auth_messages_mobile",
            "payload" => array(
                "auth" => array("client_id" => $client_id, "client_secret" => $client_secret),
                "messages" => $messages_alt
            )
        ),
        array(
            "name" => "flat_credentials",
            "payload" => array(
                "client_id" => $client_id,
                "client_secret" => $client_secret,
                "messages" => $messages_alt
            )
        )
    );

    if ($payload_mode !== "auto") {
        $filtered = array();
        for ($i = 0; $i < count($payload_variants); $i++) {
            if ($payload_variants[$i]["name"] === $payload_mode) {
                $filtered[] = $payload_variants[$i];
                break;
            }
        }
        if (count($filtered) > 0) {
            $payload_variants = $filtered;
        }
    }

    $final_success = false;
    $final_http = 0;
    $final_message = "Unknown provider error";
    $last_status = "Rejected";

    for ($endpoint_idx = 0; $endpoint_idx < count($api_endpoints); $endpoint_idx++) {
        $api_url = $api_endpoints[$endpoint_idx];
        for ($variant_idx = 0; $variant_idx < count($payload_variants); $variant_idx++) {
            $variant = $payload_variants[$variant_idx];
            $payload = $variant["payload"];
            list($response, $http_code, $curl_error) = post_json($api_url, $payload);
            $final_http = intval($http_code);

            $request_json = mysqli_real_escape_string($conn, json_encode($payload));
            $response_body = $curl_error ? $curl_error : (string)$response;
            $response_escaped = mysqli_real_escape_string($conn, $response_body);
            $provider_tag = $provider_name . "[" . $api_url . "|" . $variant["name"] . "]";

            if ($curl_error) {
                $last_status = "TransportError";
                $final_message = "Transport error: " . substr($curl_error, 0, 200);
                mysqli_query($conn, "INSERT INTO sms_api_logs(provider,request_body,response_body,http_code,status,created_at) VALUES('" . mysqli_real_escape_string($conn, $provider_tag) . "','" . $request_json . "','" . $response_escaped . "',0,'TransportError','" . $now . "')");
                continue;
            }

            $json_data = json_decode($response);
            $ok_status = false;
            if (is_object($json_data)) {
                if ((isset($json_data->status) && ($json_data->status === true || $json_data->status === "success")) || (isset($json_data->success) && $json_data->success === true)) {
                    $ok_status = true;
                }
            }

            if ($http_code >= 200 && $http_code < 300 && ($ok_status || stripos((string)$response, "success") !== false || stripos((string)$response, "accepted") !== false)) {
                $final_success = true;
                $final_message = $provider_name . " accepted";
                mysqli_query($conn, "INSERT INTO sms_api_logs(provider,request_body,response_body,http_code,status,created_at) VALUES('" . mysqli_real_escape_string($conn, $provider_tag) . "','" . $request_json . "','" . $response_escaped . "','" . intval($http_code) . "','Success','" . $now . "')");
                break 2;
            }

            if (is_object($json_data) && isset($json_data->message) && trim($json_data->message) !== "") {
                $final_message = trim($json_data->message);
            } else if (!empty($response)) {
                $final_message = substr(trim(strip_tags((string)$response)), 0, 220);
            } else {
                $final_message = "HTTP " . intval($http_code);
            }
            $last_status = ($http_code >= 500 || $http_code == 0) ? "Retrying" : "Rejected";
            mysqli_query($conn, "INSERT INTO sms_api_logs(provider,request_body,response_body,http_code,status,created_at) VALUES('" . mysqli_real_escape_string($conn, $provider_tag) . "','" . $request_json . "','" . $response_escaped . "','" . intval($http_code) . "','" . mysqli_real_escape_string($conn, $last_status) . "','" . $now . "')");
        }
    }

    return array($final_success, $final_http, $final_message, $last_status);
}

while ($outgoing = mysqli_fetch_assoc($q)) {
    $msisdn = preg_replace('/\D+/', '', $outgoing['phone_number']);
    $provider_name = detect_provider_name($msisdn, $providers);
    if ($provider_name === "") {
        $fail_reason = mysqli_real_escape_string($conn, 'Unsupported country code. Use 255, 254 or 256.');
        mysqli_query($conn, "UPDATE outgoing SET attempts=attempts+1,sms_status='Failed',smsc_id='" . $fail_reason . "' WHERE sms_id='" . intval($outgoing['sms_id']) . "'");
        continue;
    }

    $reference = substr(hash("sha256", $outgoing['sms_id'] . "|" . $outgoing['date_created'] . "|" . $msisdn), 0, 24);
    $source = trim($outgoing['sender_id']);
    if ($source === "") {
        $source = "VLL SMS";
    }

    if (!isset($routed[$provider_name])) {
        $routed[$provider_name] = array(
            "messages" => array(),
            "sms_ids" => array(),
            "sms_attempts" => array()
        );
    }
    $routed[$provider_name]["messages"][] = array(
        "text" => $outgoing['message'],
        "msisdn" => $msisdn,
        "source" => $source,
        "reference" => $reference
    );
    $sms_id = intval($outgoing['sms_id']);
    $routed[$provider_name]["sms_ids"][] = $sms_id;
    $routed[$provider_name]["sms_attempts"][$sms_id] = intval($outgoing['attempts']);
}

if (empty($routed)) {
    exit;
}

foreach ($routed as $provider_name => $bucket) {
    $provider_config = $providers[$provider_name]["config"];
    list($final_success, $final_http, $final_message, $last_status) = provider_send_batch($conn, $provider_name, $provider_config, $bucket["messages"], $now);

    foreach ($bucket["sms_ids"] as $sms_id) {
        $next_attempt = isset($bucket["sms_attempts"][$sms_id]) ? ($bucket["sms_attempts"][$sms_id] + 1) : 1;
        $is_last_attempt = ($next_attempt >= $max_attempts);
        if ($final_success) {
            mysqli_query($conn, "UPDATE outgoing SET attempts=attempts+1,sms_status='Sent',smsc_id='" . mysqli_real_escape_string($conn, $provider_name . " accepted") . "' WHERE sms_id='" . intval($sms_id) . "'");
        } else if ($final_http >= 500 || $final_http == 0 || $last_status == "TransportError" || $last_status == "Retrying") {
            if ($is_last_attempt) {
                mysqli_query($conn, "UPDATE outgoing SET attempts=attempts+1,sms_status='Failed',smsc_id='" . mysqli_real_escape_string($conn, substr($final_message, 0, 220)) . "' WHERE sms_id='" . intval($sms_id) . "'");
            } else {
                mysqli_query($conn, "UPDATE outgoing SET attempts=attempts+1,smsc_id='" . mysqli_real_escape_string($conn, substr($final_message, 0, 220)) . "' WHERE sms_id='" . intval($sms_id) . "'");
            }
        } else {
            mysqli_query($conn, "UPDATE outgoing SET attempts=attempts+1,sms_status='Failed',smsc_id='" . mysqli_real_escape_string($conn, substr($final_message, 0, 220)) . "' WHERE sms_id='" . intval($sms_id) . "'");
        }
    }
}
