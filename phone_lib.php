<?php

/**
 * Multi-country MSISDN helpers for FastHub / contact storage.
 * TZ 255, KE 254, UG 256; OTHER = full international digits only (no local 0-prefix rules).
 */

function phone_region_codes()
{
    return array(
        'TZ' => '255',
        'KE' => '254',
        'UG' => '256',
    );
}

function phone_normalize_region($region)
{
    $region = strtoupper(trim((string) $region));
    $region = preg_replace('/[^A-Z]/', '', $region);
    if ($region === 'KENYA') {
        $region = 'KE';
    }
    if ($region === 'UGANDA') {
        $region = 'UG';
    }
    if ($region === 'TANZANIA') {
        $region = 'TZ';
    }
    if ($region === 'OTHER' || $region === 'OTHERS' || $region === 'INTL' || $region === 'INTERNATIONAL') {
        return 'OTHER';
    }
    $codes = phone_region_codes();
    if (!isset($codes[$region])) {
        return 'TZ';
    }
    return $region;
}

function normalize_contact_phone($raw, $region)
{
    $digits = preg_replace('/\D+/', '', (string) $raw);
    if ($digits === '') {
        return '';
    }
    if (strpos($digits, '00') === 0) {
        $digits = substr($digits, 2);
    }

    $region = phone_normalize_region($region);
    if ($region === 'OTHER') {
        return $digits;
    }

    $codes = phone_region_codes();
    $cc = $codes[$region];

    if (strlen($digits) >= 12 && strpos($digits, $cc) === 0) {
        return $digits;
    }
    if (strlen($digits) == 10 && $digits[0] === '0') {
        return $cc . substr($digits, 1);
    }
    if (strlen($digits) == 9) {
        return $cc . $digits;
    }

    return $digits;
}

function is_valid_contact_msisdn($digits, $region)
{
    $digits = preg_replace('/\D+/', '', (string) $digits);
    if ($digits === '' || !ctype_digit($digits)) {
        return false;
    }
    $region = phone_normalize_region($region);
    $len = strlen($digits);

    if ($region === 'OTHER') {
        return $len >= 10 && $len <= 15;
    }

    if ($len !== 12) {
        return false;
    }
    $codes = phone_region_codes();
    $cc = $codes[$region];
    return substr($digits, 0, 3) === $cc;
}

/**
 * Normalize numbers pasted into compose / free-text lists.
 * Keeps legacy behaviour: bare 9-digit or 0XXXXXXXXX numbers default to Tanzania.
 */
function normalize_recipient_msisdn($raw)
{
    $digits = preg_replace('/\D+/', '', trim((string) $raw));
    if ($digits === '') {
        return '';
    }
    if (strpos($digits, '00') === 0) {
        $digits = substr($digits, 2);
    }

    if (preg_match('/^(255|254|256)/', $digits) && strlen($digits) >= 12 && strlen($digits) <= 15) {
        return $digits;
    }

    if (strlen($digits) == 10 && $digits[0] === '0') {
        return '255' . substr($digits, 1);
    }
    if (strlen($digits) == 9) {
        return '255' . $digits;
    }

    if (strlen($digits) >= 10 && strlen($digits) <= 15 && ctype_digit($digits)) {
        return $digits;
    }

    return '';
}

function is_valid_outgoing_msisdn($digits)
{
    $digits = preg_replace('/\D+/', '', (string) $digits);
    if ($digits === '' || !ctype_digit($digits)) {
        return false;
    }
    $len = strlen($digits);
    return $len >= 10 && $len <= 15;
}

/**
 * FastHub bulk route used here delivers SMS to Tanzanian mobiles only (+255, 12-digit MSISDN).
 */
function phone_is_fasthub_tanzania_msisdn($digits)
{
    $digits = preg_replace('/\D+/', '', (string) $digits);
    return strlen($digits) === 12 && strpos($digits, '255') === 0;
}
