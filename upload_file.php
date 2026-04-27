<?php
include "db/dblink.php";

if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("location:signout.php");
} else {
    $q = mysqli_query($conn, "SELECT * FROM users WHERE user_id='" . $_SESSION['user_id'] . "'");
    $found = mysqli_num_rows($q);
    if ($found) {
        $user = mysqli_fetch_assoc($q);
        if ($user['status'] == "Pending") {
            header("location:verification.php");
        }
    } else {
        header("location:signout.php");
    }
}

include 'simple_xlsx.php';


$q=mysqli_query($conn,"DELETE FROM custom_sms WHERE user_id='".$_SESSION['user_id']."'");

$sender_id = mysqli_real_escape_string($conn, $_POST['sender_id']);
$template = mysqli_real_escape_string($conn, $_POST['message']);

$date_created = time();
$user_id = $user['user_id'];


$target_dir = "uploads/";
$target_file = $target_dir . basename($_FILES["file_name"]["name"]);
$file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

// Check file size
if ($_FILES["file_name"]["size"] < 2000000) {

    // Allow certain file formats
    if ($file_type == "xls" || $file_type == "xlsx") {
        if (move_uploaded_file($_FILES["file_name"]["tmp_name"], $target_file)) {
            $xlsx = new SimpleXLSX($target_file);

            $columns = array();
            $headers = $xlsx->rows();

            for ($i = 0; $i < count($headers[0]); $i++) {
                $stub = "{" . $headers[0][$i] . "}";
                array_push($columns, $stub);
            }

            $rows = 0;
            foreach ($xlsx->rows() as $fields) {
                if ($rows > 0) {
                    $message = $template;
                    for ($i = 0; $i < count($columns); $i++) {
                        $message = str_replace($columns[$i], $fields[$i], $message);
                    }

                    $phone_number = str_replace(" ","", $fields[0]);
                    $credits = ceil(strlen($message) / 160);

                    $schedule = mysqli_real_escape_string($conn, $_POST['schedule']);

                    $start_date = strtotime(mysqli_real_escape_string($conn, $_POST['start_date']));
                    $end_date = strtotime(mysqli_real_escape_string($conn, $_POST['end_date']));

                    $send_hour = mysqli_real_escape_string($conn, $_POST['send_hour']);
                    $send_minute = mysqli_real_escape_string($conn, $_POST['send_minute']);


                    $now = time();

                    $attempts = 0;
                    $sms_status = "Pending";
                    $user_id = $_SESSION['user_id'];

if(!empty($phone_number) && !empty($message)){
                    switch ($schedule) {
                        case "None":
                            $date_created = strtotime(date("d-m-Y", $start_date) . " " . $send_hour . ":" . $send_minute);
                            $q = mysqli_query($conn, "INSERT INTO custom_sms(phone_number,sender_id,message,credits,schedule,start_date,end_date,date_created,attempts,sms_status,user_id) VALUES('" . $phone_number . "','" . $sender_id . "','" . $message . "','" . $credits . "','" . $schedule . "','" . $start_date . "','" . $end_date . "','" . $date_created . "','" . $attempts . "','" . $sms_status . "','" . $user_id . "')");
                            break;

                        case "Daily":

                            $next_date = $start_date;
                            while ($next_date <= $end_date) {
                                $date_created = strtotime(date("d-m-Y", $next_date) . " " . $send_hour . ":" . $send_minute);
                                $q = mysqli_query($conn, "INSERT INTO custom_sms(phone_number,sender_id,message,credits,schedule,start_date,end_date,date_created,attempts,sms_status,user_id) VALUES('" . $phone_number . "','" . $sender_id . "','" . $message . "','" . $credits . "','" . $schedule . "','" . $start_date . "','" . $end_date . "','" . $date_created . "','" . $attempts . "','" . $sms_status . "','" . $user_id . "')");
                                $next_date =  strtotime("+1 days", $next_date);
                            }
                            break;

                        case "Weekly":

                            $next_date = $start_date;
                            while ($next_date <= $end_date) {
                                $date_created = strtotime(date("d-m-Y", $next_date) . " " . $send_hour . ":" . $send_minute);
                                $q = mysqli_query($conn, "INSERT INTO custom_sms(phone_number,sender_id,message,credits,schedule,start_date,end_date,date_created,attempts,sms_status,user_id) VALUES('" . $phone_number . "','" . $sender_id . "','" . $message . "','" . $credits . "','" . $schedule . "','" . $start_date . "','" . $end_date . "','" . $date_created . "','" . $attempts . "','" . $sms_status . "','" . $user_id . "')");
                                $next_date =  strtotime("+1 weeks", $next_date);
                            }
                            break;


                        case "Monthly":

                            $next_date = $start_date;
                            while ($next_date <= $end_date) {
                                $date_created = strtotime(date("d-m-Y", $next_date) . " " . $send_hour . ":" . $send_minute);
                                $q = mysqli_query($conn, "INSERT INTO custom_sms(phone_number,sender_id,message,credits,schedule,start_date,end_date,date_created,attempts,sms_status,user_id) VALUES('" . $phone_number . "','" . $sender_id . "','" . $message . "','" . $credits . "','" . $schedule . "','" . $start_date . "','" . $end_date . "','" . $date_created . "','" . $attempts . "','" . $sms_status . "','" . $user_id . "')");
                                $next_date =  strtotime("+1 months", $next_date);
                            }
                            break;
                    }
}
                }
                $rows += 1;
            }
            header("location:preview.php");
        } else {
            header("location:file_sms.php?r=operation failed, please try again later");
        }
    } else {
        header("location:file_sms.php?r=Invalid file type");
    }
} else {
    header("location:file_sms.php?r=File size is too large");
}
