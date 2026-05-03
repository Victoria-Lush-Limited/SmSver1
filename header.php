<?php
    $b = mysqli_query($conn, "SELECT (SUM(allocated)-SUM(consumed)) AS balance FROM transactions WHERE user_id='" . $_SESSION['user_id'] . "'");
    $bal = mysqli_fetch_assoc($b);
    $sms_balance = $bal['balance'];
?>

<header>
    <div class="logo">
        Bulk SMS
    </div>
    <ul>
        <li>SMS Credits: <b><span id="sms_balance"><?php echo number_format($sms_balance); ?></span></b></li>
        <li><label for="buy_credits" class="buy-button"><i class="fas fa-money-bill fa-l"></i>Buy Credits</label></li>
        <li><a href="account.php"><i class="fas fa-user fa-l"></i><?php echo $user['client_name']; ?></a></li>
    </ul>
</header>

<input type="checkbox" name="buy_credits" id="buy_credits">
<div id="buy_credits_modal">
    <div class="modal-wrapper">
        <div class="modal-header">
            <div class="modal-title">
                Buy SMS Credits
            </div>
            <div class="modal-close">
                <label for="buy_credits"><i class="fas fa-times fa-2x"></i></label>
            </div>
        </div>
        <div class="modal-content" id="buy_credits_modal_content">
            <form id="purchase_form" action="buy_credits.php" method="post">
                <div class="form-field">
                    <label for="">SMS Quantity</label>
                    <input type="text" name="quantity" id="quantity" placeholder="" maxlength="7"  onkeyup="get_total_cost()" onchange="get_total_cost()" onblur="get_total_cost()" >
                </div>
                <div class="form-field">
                    <label class="cost-box">Total Cost:<span id="total_cost"></span></label>
                </div>
                <div class="form-field">
                    <div class="send-button" onclick="place_order()"><i class="fas fa-shopping-cart"></i>Buy Credits</div>
                </div>
                <div class="form-field">
                    <div class="form-errors" id="form_errors"></div>
                </div>
            </form>

            <table>
                <tr style="background-color:whitesmoke; font-size:16px; font-weight:900;">
                    <td>SMS Quantity</td>
                    <td align="center">Price (TSH)</td>
                </tr>
                <?php
                $price_array = "";
                $q = mysqli_query($conn, "SELECT * FROM pricing WHERE scheme_id='" . $user['scheme_id'] . "' ORDER BY min_sms ASC");
                while ($price = mysqli_fetch_assoc($q)) {
                    $price_array .= $price['min_sms'] . "-" . $price['max_sms'] . "@" . $price['price'] . ",";

                    if ($price['max_sms'] == 0) {
                        $max_sms = "Above";
                    } else {
                        $max_sms = number_format($price['max_sms']);
                    }
                ?>
                    <tr>
                        <td><?php echo number_format($price['min_sms']) . " - " . $max_sms; ?></td>
                        <td align="center"><?php echo round($price['price']) ?></td>
                    </tr>
                <?php } ?>
            </table>
            <input type="hidden" id="price_array" value="<?php echo $price_array; ?>">
        </div>
    </div>
</div>

<script>
    setInputFilter(document.getElementById("quantity"), function(value) {
        return /^-?\d*$/.test(value);
    });
</script>