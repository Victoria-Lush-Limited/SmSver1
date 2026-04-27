<?php
include "db/dblink.php";

$sms_ids= explode(",",str_replace("item_","",mysqli_real_escape_string($conn,$_GET['sms_ids'])));
foreach($sms_ids as $sms_id){
    $q=mysqli_query($conn,"UPDATE outgoing SET sms_status='Cancelled' WHERE sms_id='".$sms_id."' AND user_id='".$_SESSION['user_id']."'");
}