<?php
include "db/dblink.php";

if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("location:signout.php");
    exit;
}

$uid = mysqli_real_escape_string($conn, (string) $_SESSION['user_id']);
$q = mysqli_query($conn, "SELECT * FROM users WHERE user_id='" . $uid . "' AND status='Active'");
if (!$q || mysqli_num_rows($q) < 1) {
    header("location:signout.php");
    exit;
}
$user = mysqli_fetch_assoc($q);

function vll_incoming_has_extended_cols($conn)
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }
    $cache = false;
    $chk = @mysqli_query($conn, "SHOW COLUMNS FROM `incoming` LIKE 'segment'");
    if ($chk && mysqli_num_rows($chk) > 0) {
        $cache = true;
    }
    return $cache;
}

$incomingExtended = vll_incoming_has_extended_cols($conn);
$incomingHasRead = false;
$chkRead = @mysqli_query($conn, "SHOW COLUMNS FROM `incoming` LIKE 'is_read'");
if ($chkRead && mysqli_num_rows($chkRead) > 0) {
    $incomingHasRead = true;
}

$senderFilter = isset($_GET['sender_id']) ? trim((string) $_GET['sender_id']) : '';
$senderFilterEsc = mysqli_real_escape_string($conn, $senderFilter);
$segmentFilter = isset($_GET['segment']) ? trim((string) $_GET['segment']) : '';
$segmentFilterEsc = mysqli_real_escape_string($conn, $segmentFilter);
$readFilter = isset($_GET['is_read']) ? trim((string) $_GET['is_read']) : '';
if ($readFilter !== '0' && $readFilter !== '1') {
    $readFilter = '';
}
$autoRefresh = isset($_GET['autorefresh']) ? (int) $_GET['autorefresh'] : 0;
if ($autoRefresh < 10) {
    $autoRefresh = 0;
}

$senders = array();
$rsSenders = mysqli_query($conn, "SELECT sender_id FROM senders WHERE id_status='Active' AND (user_id='" . $uid . "' OR id_type='Public' OR id_type='Global') ORDER BY sender_id ASC");
if ($rsSenders) {
    while ($r = mysqli_fetch_assoc($rsSenders)) {
        $senders[] = (string) $r['sender_id'];
    }
}

$autoReplyRows = array();
$autoWhere = "1=1";
if ($senderFilter !== '') {
    $autoWhere .= " AND sender_id='" . $senderFilterEsc . "'";
}
$rsAuto = mysqli_query($conn, "SELECT id,sender_id,reply,scheduled_time,end_schedule,segment FROM autoreplies WHERE " . $autoWhere . " ORDER BY sender_id ASC, scheduled_time ASC, id DESC LIMIT 200");
if ($rsAuto) {
    while ($a = mysqli_fetch_assoc($rsAuto)) {
        $autoReplyRows[] = $a;
    }
}

$where = " WHERE user_id='" . $uid . "'";
if ($senderFilter !== '') {
    $where .= " AND recipient='" . $senderFilterEsc . "'";
}
if ($incomingExtended && $segmentFilter !== '') {
    $where .= " AND segment='" . $segmentFilterEsc . "'";
}
if ($incomingHasRead && $readFilter !== '') {
    $where .= " AND is_read='" . mysqli_real_escape_string($conn, $readFilter) . "'";
}

$unreadCount = 0;
if ($incomingHasRead) {
    $rsUnread = mysqli_query($conn, "SELECT COUNT(*) AS c FROM incoming WHERE user_id='" . $uid . "' AND is_read='0'");
    if ($rsUnread) {
        $rUnread = mysqli_fetch_assoc($rsUnread);
        $unreadCount = (int) ($rUnread['c'] ?? 0);
    }
}

$rows = array();
$rsIncoming = mysqli_query($conn, "SELECT * FROM incoming " . $where . " ORDER BY id DESC LIMIT 1000");
if ($rsIncoming) {
    while ($r = mysqli_fetch_assoc($rsIncoming)) {
        $rows[] = $r;
    }
}
$vll_page_description = 'Incoming SMS inbox — replies and audience messages for your sender IDs.';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include __DIR__ . '/inc/head_brand_meta.php'; ?>
    <title><?php echo $app['app_name']; ?></title>
    <link rel="stylesheet" href="css/all.css?v=20260511">
    <link rel="stylesheet" href="css/style.css?v=20260511">
    <script src="js/script.js?v=20260511"></script>
</head>
<body>
<?php include "header.php"; ?>
<div class="container">
    <div class="menu"><?php include "menu.php"; ?></div>
    <div class="content-wrapper">
        <div class="page-title">Incoming Messages</div>

        <?php if (isset($_GET['r']) && (string) $_GET['r'] !== '') { ?>
            <div class="message-failed"><?php echo htmlspecialchars((string) $_GET['r'], ENT_QUOTES, 'UTF-8'); ?></div>
        <?php } ?>

        <div class="page-content">
            <?php if ($incomingExtended) { ?>
                <div class="incoming-card" style="margin-bottom:12px;">
                    <h3 class="incoming-card-title">Auto-Reply Status Guide</h3>
                    <table class="incoming-table" width="100%" border="0" cellspacing="0" cellpadding="6">
                        <tr class="incoming-table-header">
                            <td>Status</td>
                            <td>Meaning</td>
                        </tr>
                        <tr class="incoming-row">
                            <td><b>queued</b></td>
                            <td>Auto-reply was accepted and queued for SMS dispatch.</td>
                        </tr>
                        <tr class="incoming-row">
                            <td><b>skipped_recent</b></td>
                            <td>A recent message from the same number was already handled within cooldown window.</td>
                        </tr>
                        <tr class="incoming-row">
                            <td><b>skipped_no_template</b></td>
                            <td>No matching auto-reply template exists for this sender/time/segment.</td>
                        </tr>
                        <tr class="incoming-row">
                            <td><b>insufficient_balance</b></td>
                            <td>Auto-reply template matched, but there were not enough SMS credits to send.</td>
                        </tr>
                        <tr class="incoming-row">
                            <td><b>failed_sender_row</b></td>
                            <td>Sender ID binding exists, but sender record could not be resolved for dispatch.</td>
                        </tr>
                    </table>
                </div>
            <?php } ?>

            <?php if (!$incomingExtended) { ?>
                <div class="message-failed" style="margin-bottom:12px;">
                    Incoming segmentation and auto-reply status columns are not installed yet. Run
                    <code>SmSver1/db/alter_incoming_segment_status.sql</code> (or <code>php artisan migrate</code> on vll_backend), then refresh.
                </div>
            <?php } ?>

            <form method="get" action="incoming.php" class="incoming-filters page-form">
                <div class="form-field">
                    <div class="field-label">Sender ID Filter:</div>
                    <div class="custom-input-wrapper">
                        <select name="sender_id" id="sender_id">
                            <option value="">All sender IDs</option>
                            <?php foreach ($senders as $sid) { ?>
                                <option value="<?php echo htmlspecialchars($sid, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $senderFilter === $sid ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($sid, ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                </div>
                <?php if ($incomingExtended) { ?>
                <div class="form-field">
                    <div class="field-label">Show segment (radio daypart, EAT):</div>
                    <div class="custom-input-wrapper">
                        <select name="segment" id="segment">
                            <option value="">All segments</option>
                            <?php
                            $segOpts = array('Morning show', 'Afternoon show', 'Evening show', 'Night show');
                            foreach ($segOpts as $so) {
                                ?>
                                <option value="<?php echo htmlspecialchars($so, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $segmentFilter === $so ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($so, ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                                <?php
                            }
                            ?>
                        </select>
                    </div>
                </div>
                <?php } ?>
                <?php if ($incomingHasRead) { ?>
                <div class="form-field">
                    <div class="field-label">Read status:</div>
                    <div class="custom-input-wrapper">
                        <select name="is_read" id="is_read">
                            <option value="">All messages</option>
                            <option value="0" <?php echo $readFilter === '0' ? 'selected' : ''; ?>>Unread only</option>
                            <option value="1" <?php echo $readFilter === '1' ? 'selected' : ''; ?>>Read only</option>
                        </select>
                    </div>
                </div>
                <?php } ?>
                <ul class="field-list"><li><button type="submit" class="send-button">Apply Filters</button></li></ul>
            </form>

            <div class="incoming-actions-bar">
                <?php if ($incomingHasRead) { ?>
                <span class="incoming-report-label">Unread inbox count: <b><?php echo number_format($unreadCount); ?></b></span>
                <?php } ?>
                <?php
                $liveUrl = 'incoming.php?autorefresh=20';
                if ($senderFilter !== '') { $liveUrl .= '&sender_id=' . urlencode($senderFilter); }
                if ($segmentFilter !== '') { $liveUrl .= '&segment=' . urlencode($segmentFilter); }
                if ($readFilter !== '') { $liveUrl .= '&is_read=' . urlencode($readFilter); }
                ?>
                <?php if ($autoRefresh > 0) { ?>
                    <a href="incoming.php<?php
                        $parts = array();
                        if ($senderFilter !== '') { $parts[] = 'sender_id=' . urlencode($senderFilter); }
                        if ($segmentFilter !== '') { $parts[] = 'segment=' . urlencode($segmentFilter); }
                        if ($readFilter !== '') { $parts[] = 'is_read=' . urlencode($readFilter); }
                        echo count($parts) ? ('?' . implode('&', $parts)) : '';
                    ?>" class="send-button incoming-action-btn">Live Sync: ON (Stop)</a>
                <?php } else { ?>
                    <a href="<?php echo htmlspecialchars($liveUrl, ENT_QUOTES, 'UTF-8'); ?>" class="send-button incoming-action-btn">Live Sync: OFF (Start)</a>
                <?php } ?>
                <a href="incoming_export.php?sender_id=<?php echo urlencode($senderFilter); ?>" class="send-button incoming-action-btn">
                    Download legacy CSV
                </a>
                <?php
                $repBase = 'incoming_report_export.php?sender_id=' . urlencode($senderFilter);
                if ($incomingExtended && $segmentFilter !== '') {
                    $repBase .= '&segment=' . urlencode($segmentFilter);
                }
                ?>
                <span class="incoming-report-label">Reports (UTF-8 CSV, opens in Excel):</span>
                <a href="<?php echo htmlspecialchars($repBase . '&report=all&mode=full', ENT_QUOTES, 'UTF-8'); ?>" class="send-button incoming-action-btn">All rows</a>
                <a href="<?php echo htmlspecialchars($repBase . '&report=success&mode=full', ENT_QUOTES, 'UTF-8'); ?>" class="send-button incoming-action-btn">Successful auto-reply</a>
                <a href="<?php echo htmlspecialchars($repBase . '&report=failed&mode=full', ENT_QUOTES, 'UTF-8'); ?>" class="send-button incoming-action-btn">Failed auto-reply</a>
                <a href="<?php echo htmlspecialchars($repBase . '&report=all&mode=phones', ENT_QUOTES, 'UTF-8'); ?>" class="send-button incoming-action-btn">Phone list (import)</a>
                <a href="<?php echo htmlspecialchars($repBase . '&report=success&mode=phones', ENT_QUOTES, 'UTF-8'); ?>" class="send-button incoming-action-btn">Phones · success only</a>
                <a href="<?php echo htmlspecialchars($repBase . '&report=failed&mode=phones', ENT_QUOTES, 'UTF-8'); ?>" class="send-button incoming-action-btn">Phones · failed only</a>
            </div>

            <form method="post" action="incoming_actions.php" enctype="multipart/form-data">
                <input type="hidden" name="sender_filter" value="<?php echo htmlspecialchars($senderFilter, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="segment_filter" value="<?php echo htmlspecialchars($segmentFilter, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="read_filter" value="<?php echo htmlspecialchars($readFilter, ENT_QUOTES, 'UTF-8'); ?>">

                <div class="incoming-table-wrap">
                <table class="incoming-table" width="100%" border="0" cellspacing="0" cellpadding="6">
                    <tr class="incoming-table-header">
                        <td><input type="checkbox" onclick="for(const c of document.querySelectorAll('.in-sel')){c.checked=this.checked;}"></td>
                        <td>Sender ID</td>
                        <td>Phone Number</td>
                        <td>Message</td>
                        <?php if ($incomingExtended) { ?>
                            <td>Segment</td>
                            <td>Auto-reply</td>
                        <?php } ?>
                        <?php if ($incomingHasRead) { ?>
                            <td>Read</td>
                        <?php } ?>
                        <td>Received</td>
                        <td>Actions</td>
                    </tr>
                    <?php
                    $colspan = $incomingExtended ? 8 : 6;
                    if ($incomingHasRead) {
                        $colspan += 1;
                    }
                    ?>
                    <?php if (count($rows) === 0) { ?>
                        <tr><td colspan="<?php echo (int) $colspan; ?>" class="incoming-empty">No incoming messages found.</td></tr>
                    <?php } ?>
                    <?php foreach ($rows as $r) { ?>
                        <tr class="incoming-row">
                            <td><input class="in-sel" type="checkbox" name="selected_ids[]" value="<?php echo (int) $r['id']; ?>"></td>
                            <td><?php echo htmlspecialchars((string) $r['recipient'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string) $r['sender'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string) $r['message'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <?php if ($incomingExtended) { ?>
                                <td><?php echo htmlspecialchars((string) ($r['segment'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars((string) ($r['auto_reply_status'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                            <?php } ?>
                            <?php if ($incomingHasRead) { ?>
                                <td><?php echo ((int) ($r['is_read'] ?? 0) === 1) ? 'Read' : 'Unread'; ?></td>
                            <?php } ?>
                            <td><?php echo htmlspecialchars((string) $r['created_at'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td>
                                <?php if ($incomingHasRead && (int) ($r['is_read'] ?? 0) === 0) { ?>
                                    <button name="action" value="mark_read_one" type="submit" formaction="incoming_actions.php?id=<?php echo (int) $r['id']; ?>" class="incoming-delete-btn">Mark Read</button>
                                <?php } ?>
                                <button name="action" value="delete_one" type="submit" formaction="incoming_actions.php?id=<?php echo (int) $r['id']; ?>" class="incoming-delete-btn">Delete</button>
                            </td>
                        </tr>
                    <?php } ?>
                </table>
                </div>
                <?php if ($incomingHasRead) { ?>
                <div style="margin:12px 0;">
                    <button class="send-button incoming-action-btn" type="submit" name="action" value="mark_read_selected">Mark Selected As Read</button>
                    <button class="send-button incoming-action-btn" type="submit" name="action" value="mark_read_filtered">Mark All Filtered As Read</button>
                </div>
                <?php } ?>

                <div class="incoming-card">
                    <h3 class="incoming-card-title">Reply / Template Send</h3>
                    <div class="form-field">
                        <div class="field-label">From Sender ID:</div>
                        <div class="custom-input-wrapper">
                            <select name="sender_id">
                                <option value="">Select sender ID</option>
                                <?php foreach ($senders as $sid) { ?>
                                    <option value="<?php echo htmlspecialchars($sid, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($sid, ENT_QUOTES, 'UTF-8'); ?></option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-field">
                        <div class="field-label">Template Message:</div>
                        <div class="custom-input-wrapper">
                            <textarea name="template_message" rows="4"></textarea>
                        </div>
                    </div>
                    <div class="form-field">
                        <div class="field-label">Extra Numbers (optional, comma/new line):</div>
                        <div class="custom-input-wrapper">
                            <textarea name="manual_numbers" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="form-field">
                        <div class="field-label">Upload Numbers (optional .csv/.xls/.xlsx):</div>
                        <div class="custom-input-wrapper"><input type="file" name="numbers_file"></div>
                    </div>
                    <button class="send-button" type="submit" name="action" value="send_template_selected">
                        Send Template To Selected/Uploaded Numbers
                    </button>
                </div>

                <div class="incoming-card">
                    <h3 class="incoming-card-title">Auto Reply Template (by Sender ID)</h3>
                    <div class="form-field">
                        <div class="field-label">From Sender ID:</div>
                        <div class="custom-input-wrapper">
                            <select name="auto_sender_id">
                                <option value="">Select sender ID</option>
                                <?php foreach ($senders as $sid) { ?>
                                    <option value="<?php echo htmlspecialchars($sid, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($sid, ENT_QUOTES, 'UTF-8'); ?></option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-field">
                        <div class="field-label">Auto Reply Text:</div>
                        <div class="custom-input-wrapper">
                            <textarea name="auto_reply_text" rows="4"></textarea>
                        </div>
                    </div>
                    <div class="form-field">
                        <div class="field-label">Start Time:</div>
                        <div class="custom-input-wrapper"><input type="time" name="auto_start" value="08:00"></div>
                    </div>
                    <div class="form-field">
                        <div class="field-label">End Time (optional):</div>
                        <div class="custom-input-wrapper"><input type="time" name="auto_end"></div>
                    </div>
                    <div class="form-field">
                        <div class="field-label">Show Segment:</div>
                        <div class="custom-input-wrapper">
                            <select name="auto_segment">
                                <option value="">All segments (fallback)</option>
                                <option value="Morning show">Morning show</option>
                                <option value="Afternoon show">Afternoon show</option>
                                <option value="Evening show">Evening show</option>
                                <option value="Night show">Night show</option>
                            </select>
                        </div>
                    </div>
                    <button class="send-button" type="submit" name="action" value="save_auto_reply">
                        Save Auto Reply
                    </button>
                </div>

                <div class="incoming-card">
                    <h3 class="incoming-card-title">Manage Auto Reply Templates (CRUD)</h3>
                    <div class="incoming-table-wrap">
                        <table class="incoming-table" width="100%" border="0" cellspacing="0" cellpadding="6">
                            <tr class="incoming-table-header">
                                <td>ID</td>
                                <td>Sender ID</td>
                                <td>Reply</td>
                                <td>Start</td>
                                <td>End</td>
                                <td>Segment</td>
                                <td>Actions</td>
                            </tr>
                            <?php if (count($autoReplyRows) === 0) { ?>
                                <tr><td colspan="7" class="incoming-empty">No auto-reply templates found.</td></tr>
                            <?php } ?>
                            <?php foreach ($autoReplyRows as $ar) { ?>
                                <tr class="incoming-row">
                                    <td><?php echo (int) $ar['id']; ?></td>
                                    <td><?php echo htmlspecialchars((string) $ar['sender_id'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars((string) $ar['reply'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars(substr((string) $ar['scheduled_time'], 0, 5), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars((string) ($ar['end_schedule'] ? substr((string) $ar['end_schedule'], 0, 5) : ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars((string) ($ar['segment'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td>
                                        <button name="action" value="delete_auto_reply" type="submit" formaction="incoming_actions.php?auto_id=<?php echo (int) $ar['id']; ?>" class="incoming-delete-btn">Delete</button>
                                    </td>
                                </tr>
                            <?php } ?>
                        </table>
                    </div>

                    <div class="form-field" style="margin-top:12px;">
                        <div class="field-label">Update Template ID:</div>
                        <div class="custom-input-wrapper"><input type="number" min="1" name="update_auto_id"></div>
                    </div>
                    <div class="form-field">
                        <div class="field-label">New Reply Text:</div>
                        <div class="custom-input-wrapper"><textarea name="update_auto_reply_text" rows="3"></textarea></div>
                    </div>
                    <div class="form-field">
                        <div class="field-label">New Start Time:</div>
                        <div class="custom-input-wrapper"><input type="time" name="update_auto_start"></div>
                    </div>
                    <div class="form-field">
                        <div class="field-label">New End Time (optional):</div>
                        <div class="custom-input-wrapper"><input type="time" name="update_auto_end"></div>
                    </div>
                    <div class="form-field">
                        <div class="field-label">New Segment:</div>
                        <div class="custom-input-wrapper">
                            <select name="update_auto_segment">
                                <option value="">All segments (fallback)</option>
                                <option value="Morning show">Morning show</option>
                                <option value="Afternoon show">Afternoon show</option>
                                <option value="Evening show">Evening show</option>
                                <option value="Night show">Night show</option>
                            </select>
                        </div>
                    </div>
                    <button class="send-button" type="submit" name="action" value="update_auto_reply">
                        Update Auto Reply
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php include "footer.php"; ?>
<script>
    (function () {
        var params = new URLSearchParams(window.location.search);
        if (!params.has('autorefresh')) {
            return;
        }
        var every = parseInt(params.get('autorefresh'), 10);
        if (isNaN(every) || every < 10) {
            every = 20;
        }
        setTimeout(function () {
            window.location.reload();
        }, every * 1000);
    })();
</script>
</body>
</html>
