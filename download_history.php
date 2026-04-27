<?php
include "db/dblink.php";

function filterData($str){ 
    $str = preg_replace("/\t/", "\\t", $str); 
    $str = preg_replace("/\r?\n/", "\\n", $str); 
    if(strstr($str, '"')) $str = '"' . str_replace('"', '""', $str) . '"'; 
    return $str;
}

// Excel file name for download 
$fileName = "sms-report.xls";

// Headers for download 
header("Content-Disposition: attachment; filename=\"$fileName\"");
header("Content-Type: application/xls");
header("Pragma: no-cache"); 
header("Expires: 0");


$from_date = strtotime(mysqli_real_escape_string($conn, $_GET['from_date']));
$to_date = strtotime("+1 days", strtotime(mysqli_real_escape_string($conn, $_GET['to_date'])));

$keyword = mysqli_real_escape_string($conn, $_GET['keyword']);

$now = time();

$q = mysqli_query($conn, "SELECT * FROM outgoing WHERE user_id='" . $_SESSION['user_id'] . "' AND date_created > '" . $from_date . "' AND date_created <='" . $to_date . "' AND date_created<'" . $now . "' AND (message LIKE '%" . $keyword . "%' || phone_number LIKE '%" . $keyword . "%' || sender_id LIKE '%" . $keyword . "%')");

echo "Phone \t";
echo "Sender \t";
echo "Message \t";
echo "Date Sent \t";
echo "Status \t";
echo "\n";

while ($sms = mysqli_fetch_assoc($q)) {

    echo $sms['phone_number'] . "\t";
    echo filterData($sms['sender_id']) . "\t";
    echo filterData($sms['message']) . "\t";
    echo date("d-M-Y H:i", $sms['date_created']) . "\t";
    echo $sms['sms_status'] . "\t";
    echo "\n";
}
