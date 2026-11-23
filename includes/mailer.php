<?php
declare(strict_types=1);

function fittrackSendMail(string $to, string $subject, string $html, ?string &$error = null): bool {
    $config = require __DIR__ . '/../config/mail.php';
    if (empty($config['enabled'])) { $error = 'Email is disabled in config/mail.php.'; error_log('FitTrack email not sent: ' . $error); return false; }
    $autoload = __DIR__ . '/../vendor/autoload.php';
    if (!is_file($autoload)) { $error = 'PHPMailer is missing. Run "composer install" in the project folder.'; error_log('FitTrack email not sent: ' . $error); return false; }
    require_once $autoload;
    try {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP(); $mail->Host = $config['host']; $mail->SMTPAuth = true;
        $mail->Username = $config['username']; $mail->Password = $config['password'];
        $mail->SMTPSecure = $config['encryption']; $mail->Port = (int) $config['port'];
        $mail->Timeout = 12; $mail->CharSet = 'UTF-8';
        $mail->setFrom($config['from_email'], $config['from_name']); $mail->addAddress($to);
        $mail->isHTML(true); $mail->Subject = $subject; $mail->Body = $html;
        $mail->AltBody = trim(strip_tags(str_replace(['<br>', '<br/>', '</p>'], "\n", $html)));
        $mail->send(); return true;
    } catch (Throwable $e) { $error = $e->getMessage(); error_log('FitTrack SMTP error: ' . $error); return false; }
}

function fittrackLogEmail(PDO $pdo, ?int $userId, string $recipient, string $subject, bool $sent, ?string $error): void {
    $config = require __DIR__ . '/../config/mail.php';
    $status = $sent ? 'sent' : (empty($config['enabled']) ? 'not_configured' : 'failed');
    try {
        $pdo->prepare('INSERT INTO email_logs(user_id,recipient,subject,status,error_message) VALUES(?,?,?,?,?)')
            ->execute([$userId, $recipient, $subject, $status, $sent ? null : $error]);
    } catch (Throwable $e) { error_log('FitTrack email log error: ' . $e->getMessage()); }
}

function fittrackEmailBody(string $name, string $message): string {
    return '<p>Hello ' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . ',</p><p>'
        . nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8')) . '</p><p>FitTrack</p>';
}

/** In-app notification for a member, plus a Gmail/SMTP email when $emailSubject is given. */
function fittrackNotify(PDO $pdo, int $userId, string $title, string $message, ?string $emailSubject = null): void {
    $pdo->prepare('INSERT INTO notifications (user_id,title,message) VALUES (?,?,?)')->execute([$userId, $title, $message]);
    if (!$emailSubject) return;
    $q = $pdo->prepare('SELECT email,name FROM users WHERE id=?'); $q->execute([$userId]); $u = $q->fetch();
    if (!$u) return;
    $err = null;
    $sent = fittrackSendMail($u['email'], $emailSubject, fittrackEmailBody($u['name'], $message), $err);
    fittrackLogEmail($pdo, $userId, $u['email'], $emailSubject, $sent, $err);
}

/** Email the admin(s): config 'admin_email' if set, otherwise every account with role = admin. */
function fittrackEmailAdmins(PDO $pdo, string $subject, string $message): void {
    $config = require __DIR__ . '/../config/mail.php';
    $targets = [];
    foreach (array_filter(array_map('trim', explode(',', (string) ($config['admin_email'] ?? '')))) as $addr) {
        if (filter_var($addr, FILTER_VALIDATE_EMAIL)) $targets[$addr] = ['id' => null, 'name' => 'Admin'];
    }
    if (!$targets) {
        foreach ($pdo->query("SELECT id,name,email FROM users WHERE role='admin'")->fetchAll() as $a) {
            $targets[$a['email']] = ['id' => (int) $a['id'], 'name' => $a['name']];
        }
    }
    foreach ($targets as $email => $t) {
        $err = null;
        $sent = fittrackSendMail($email, $subject, fittrackEmailBody($t['name'], $message), $err);
        fittrackLogEmail($pdo, $t['id'], $email, $subject, $sent, $err);
    }
}
