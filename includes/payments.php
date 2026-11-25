<?php
declare(strict_types=1);

/**
 * Shared "payment portal" for gym memberships and coaching sessions.
 * Two methods: cash (pay at reception, admin confirms) and QR (scan, pay, upload screenshot).
 */

const FITTRACK_PROOF_MAX_BYTES = 4 * 1024 * 1024;

function fittrackPaymentConfig(): array
{
    static $config = null;

    if ($config === null) {
        $config = require __DIR__ . '/../config/payment.php';
    }

    return $config;
}

/** 'cash' | 'qr' | null (anything else). */
function fittrackNormalizePaymentMethod(mixed $value): ?string
{
    $method = is_string($value) ? strtolower(trim($value)) : '';

    return in_array($method, ['cash', 'qr'], true) ? $method : null;
}

function fittrackPaymentMethodLabel(?string $method): string
{
    return match ($method) {
        'cash' => 'Cash',
        'qr' => 'QR payment',
        default => '—',
    };
}

function fittrackMoney(?float $amount): string
{
    return $amount === null ? '' : 'Rs. ' . number_format($amount, 2);
}

/**
 * Path (relative to the project root) of the QR image shown to members, or null.
 * Save your real QR as assets/images/payment-qr.png (or .jpg/.jpeg/.webp) - it wins
 * over the bundled sample placeholder (payment-qr.svg). Or set 'qr_image' in config/payment.php.
 */
function fittrackQrImage(): ?string
{
    $config = fittrackPaymentConfig();
    $custom = trim((string) ($config['qr_image'] ?? ''));

    if ($custom !== '' && is_file(__DIR__ . '/../' . $custom)) {
        return $custom;
    }

    foreach (['png', 'jpg', 'jpeg', 'webp', 'svg'] as $extension) {
        $relative = 'assets/images/payment-qr.' . $extension;

        if (is_file(__DIR__ . '/../' . $relative)) {
            return $relative;
        }
    }

    return null;
}

/**
 * Validates and stores an uploaded payment screenshot/receipt.
 * Returns the stored file name, or null with $error set.
 */
function fittrackSavePaymentProof(array $file, ?string &$error): ?string
{
    $code = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);

    if ($code === UPLOAD_ERR_INI_SIZE || $code === UPLOAD_ERR_FORM_SIZE) {
        $error = 'Payment proof must be 4 MB or smaller.';

        return null;
    }

    if (empty($file['name']) || $code !== UPLOAD_ERR_OK) {
        $error = 'Upload your payment screenshot or receipt to complete the QR payment.';

        return null;
    }

    if ((int) ($file['size'] ?? 0) > FITTRACK_PROOF_MAX_BYTES) {
        $error = 'Payment proof must be 4 MB or smaller.';

        return null;
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = (string) $finfo->file((string) $file['tmp_name']);
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'application/pdf' => 'pdf'];

    if (!isset($allowed[$mime])) {
        $error = 'Upload a JPG, PNG or PDF file.';

        return null;
    }

    $dir = __DIR__ . '/../uploads/payment-proofs';

    if (!is_dir($dir)) {
        mkdir($dir, 0750, true);
    }
    $name = bin2hex(random_bytes(18)) . '.' . $allowed[$mime];

    if (!move_uploaded_file((string) $file['tmp_name'], $dir . '/' . $name)) {
        $error = 'Could not save the uploaded proof. Please try again.';

        return null;
    }

    return $name;
}

/**
 * Prints the Cash / QR chooser. Put it inside a <form method="post" enctype="multipart/form-data">.
 * Fields posted: payment_method (cash|qr), payment_reference, proof (file, QR only).
 * Behaviour (show/hide panels, amount updates) lives in assets/js/app.js.
 */
function fittrackPaymentPortalFields(string $selected = 'cash', string $amountText = '', string $reference = ''): void
{
    $config = fittrackPaymentConfig();
    $qr = fittrackQrImage();
    $selected = ($selected === 'qr' && $qr !== null) ? 'qr' : 'cash';
    $cashText = (string) ($config['cash_instructions'] ?? 'Pay the amount in cash at the gym reception.');
    $qrName = (string) ($config['qr_account_name'] ?? '');
    $qrNote = (string) ($config['qr_note'] ?? '');
?>
<div class="pay-portal" data-pay-portal>
    <div class="pay-options">
        <label class="pay-option<?= $selected === 'cash' ? ' selected' : '' ?>"> <input type="radio" name="payment_method" value="cash" data-pay-method <?= $selected === 'cash' ? 'checked' : '' ?>> <span class="pay-option-body"><strong>💵 Cash</strong><small>Pay at the gym reception. The admin confirms once the cash is received.</small></span> </label>
        <label class="pay-option<?= $selected === 'qr' ? ' selected' : '' ?><?= $qr === null ? ' disabled' : '' ?>"> <input type="radio" name="payment_method" value="qr" data-pay-method <?= $selected === 'qr' ? 'checked' : '' ?> <?= $qr === null ? 'disabled' : '' ?>> <span class="pay-option-body"><strong>▦ QR payment</strong><small><?= $qr === null ? 'Not available yet - ask the gym to set up the QR code.' : 'Scan the QR code, pay, then upload the screenshot.' ?></small></span> </label>
    </div>
    <div class="pay-panel" data-pay-panel="cash">
        <p><?= e($cashText) ?></p>
        <?php if ($amountText !== ''): ?>
            <p class="pay-amount">Amount to pay: <strong data-pay-amount><?= e($amountText) ?></strong></p>
        <?php endif; ?>
    </div>
    <?php if ($qr !== null): ?>
        <div class="pay-panel" data-pay-panel="qr">
            <div class="qr-box">
                <img class="qr-image" src="<?= e($qr) ?>" alt="FitTrack payment QR code">
                <div class="qr-info">
                    <?php if ($amountText !== ''): ?>
                        <p class="pay-amount">Amount to pay: <strong data-pay-amount><?= e($amountText) ?></strong></p>
                    <?php endif; ?>
                    <?php if ($qrName !== ''): ?>
                        <p>Pay to: <strong><?= e($qrName) ?></strong></p>
                    <?php endif; ?>
                    <?php if ($qrNote !== ''): ?>
                        <p class="muted"><?= e($qrNote) ?></p>
                    <?php endif; ?>
                    <ol class="pay-steps">
                        <li>Open your banking or wallet app and scan the QR code.</li>
                        <li>Pay the exact amount shown.</li>
                        <li>Upload the payment screenshot below.</li>
                    </ol>
                </div>
            </div>
            <div class="form-grid">
                <div><label>Transaction / reference no. (optional)</label><input name="payment_reference" maxlength="120" value="<?= e($reference) ?>"></div>
                <div><label>Payment screenshot (JPG, PNG or PDF, max 4 MB)</label><input type="file" name="proof" accept="image/jpeg,image/png,application/pdf"></div>
            </div>
        </div>
    <?php endif; ?>
</div>
<?php } ?>
