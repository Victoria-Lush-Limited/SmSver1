<?php
include "db/dblink.php";

if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("location:signout.php");
} else {
    $q = mysqli_query($conn, "SELECT * FROM users WHERE user_id='" . $_SESSION['user_id'] . "' AND status='Active'");
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

$from_date=strtotime(date("d-m-Y",time()));
$to_date=time();

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $app['app_name']; ?></title>
    <link type="text/css" href="css/ui-lightness/jquery-ui-1.8.13.custom.css" rel="Stylesheet" />
    <script type="text/javascript" src="js/jquery-1.5.1.min.js"></script>
    <script type="text/javascript" src="js/jquery-ui-1.8.13.custom.min.js"></script>

    <link rel="stylesheet" href="css/all.css">
    <link rel="stylesheet" href="css/style.css">
    <script src="js/script.js"></script>
    <script>
        $(function() {
            $("#from_date").datepicker({
                dateFormat: 'dd-mm-yy',
                maxDate: '<?php echo date('d-m-Y', time()); ?>',
            });
            $("#to_date").datepicker({
                dateFormat: 'dd-mm-yy',
                maxDate: '<?php echo date('d-m-Y', time()); ?>',
            });
        });
    </script>
</head>

<body>
    <?php include "header.php"; ?>
    <div class="container">
        <div class="menu">
            <?php include "menu.php"; ?>
        </div>
        <div class="content-wrapper">
            <div class="page-title">History</div>
            <div class="page-header">
                <ul class="page-menu">
                    <li><a onclick="download_history()"><i class="fas fa-download fa-s"></i>Download</a></li>
                </ul>
                <div class="page-options">
                    <label for="">Dates:</label>
                    <ul>
                        <li><input type="text" class="date-field" id="from_date" name="from_date" value="<?php echo date("d-m-Y",$from_date);?>" onchange="get_history(1,document.getElementById('per_page').value)" placeholder="From Date"><i class="fas fa-calendar-alt fa-s"></i></li>
                        <li style="padding-top:5px; font-weight:bold;">-</li>
                        <li><input type="text" class="date-field" id="to_date" name="to_date" value="<?php echo date("d-m-Y",$to_date);?>" onchange="get_history(1,document.getElementById('per_page').value)" placeholder="To Date"><i class="fas fa-calendar-alt fa-s"></i></li>
                        <li><input type="text" id="keyword" name="keyword" placeholder="Search Keyword"   onchange="get_history(1,document.getElementById('per_page').value)"><i class="fas fa-search fa-s"  onclick="get_history(1,document.getElementById('per_page').value)"></i></li>
                    </ul>
                </div>
            </div>
            <div class="page-content" id="page-content">

            </div>
        </div>
    </div>
    <?php include "footer.php";?>
    <script>
        get_history(1,10);
    </script>
</body>

</html>