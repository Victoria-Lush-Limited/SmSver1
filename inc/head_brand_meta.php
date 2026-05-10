<?php
/**
 * Optional: set $vll_page_description (string) before include for a page-specific meta description.
 */
$__vll_desc = 'Victoria Lush SMS — bulk messaging, contacts, templates, senders, and campaigns.';
if (isset($vll_page_description) && is_string($vll_page_description) && trim($vll_page_description) !== '') {
    $__vll_desc = trim($vll_page_description);
}
?>
    <meta name="description" content="<?php echo htmlspecialchars($__vll_desc, ENT_QUOTES, 'UTF-8'); ?>">
    <meta name="theme-color" content="#C41E3A">
