<?php

/**
 * Batched INSERTs into `outgoing` for large sends (fewer round-trips, shorter transactions).
 * Chunk size: env VLL_SEND_INSERT_CHUNK (default 200, clamped 40–350).
 */
function vll_send_insert_chunk_size()
{
    $c = (int) vll_env("VLL_SEND_INSERT_CHUNK", "200");
    if ($c < 40) {
        return 40;
    }
    if ($c > 350) {
        return 350;
    }
    return $c;
}

/**
 * @param mysqli $conn
 * @param array<int, string> $recipient_list MSISDNs (already validated)
 * @param string $sender_id escaped for SQL
 * @param string $message escaped for SQL
 * @param int|string $credits
 * @param string $schedule escaped
 * @param int|string $start_date
 * @param int|string $end_date
 * @param int|string $date_created
 * @param int|string $attempts
 * @param string $sms_status escaped
 * @param string $user_id escaped
 * @param string $smsc_id escaped (e.g. Queued for provider)
 */
function vll_flush_outgoing_values($conn, array $valueTuples)
{
    if (count($valueTuples) === 0) {
        return true;
    }
    $sql = "INSERT INTO outgoing(phone_number,sender_id,message,credits,schedule,start_date,end_date,date_created,attempts,sms_status,user_id,smsc_id) VALUES "
        . implode(",", $valueTuples);
    return (bool) mysqli_query($conn, $sql);
}
