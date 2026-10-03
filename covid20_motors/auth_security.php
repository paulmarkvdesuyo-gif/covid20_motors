<?php

const LOGIN_FAILURE_LIMIT = 10;
const LOGIN_FAILURE_WINDOW_SECONDS = 900;
const LOGIN_LOCKOUT_SECONDS = 900;
const CUSTOMER_LOGIN_FAILURE_LIMIT = 5;
const CUSTOMER_LOGIN_LOCKOUT_SECONDS = 300;

function provisionInitialAdmin(PDO $pdo): bool
{
    $initialPassword = getenv('INITIAL_ADMIN_PASSWORD');
    if ($initialPassword === false && defined('APP_INITIAL_ADMIN_PASSWORD')) {
        $initialPassword = APP_INITIAL_ADMIN_PASSWORD;
    }
    if ($initialPassword === false || strlen($initialPassword) < 12) {
        return false;
    }

    $initialEmail = getenv('INITIAL_ADMIN_EMAIL');
    if ($initialEmail === false && defined('APP_INITIAL_ADMIN_EMAIL')) {
        $initialEmail = APP_INITIAL_ADMIN_EMAIL;
    }
    if ($initialEmail === false || filter_var($initialEmail, FILTER_VALIDATE_EMAIL) === false) {
        $initialEmail = 'admin@covid20smotors.com';
    }

    $columns = $pdo->query('SHOW COLUMNS FROM users')->fetchAll(PDO::FETCH_COLUMN);
    $passwordHash = password_hash($initialPassword, PASSWORD_DEFAULT);

    $admins = $pdo->query("SELECT id, password FROM users WHERE role = 'admin'")->fetchAll(PDO::FETCH_ASSOC);
    if ($admins) {
        foreach ($admins as $admin) {
            $storedPassword = (string)$admin['password'];
            $passwordInfo = password_get_info($storedPassword);
            $isHashed = isset($passwordInfo['algo']) && $passwordInfo['algo'] !== null;
            $isLegacyDefault = $isHashed
                ? password_verify('admin123', $storedPassword)
                : hash_equals('admin123', $storedPassword);

            if ($isLegacyDefault) {
                $statement = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
                $statement->execute([$passwordHash, (int)$admin['id']]);
                return true;
            }
        }

        return false;
    }

    if (in_array('username', $columns, true)) {
        $statement = $pdo->prepare("INSERT INTO users (username, fullname, email, password, role) VALUES (?, ?, ?, ?, 'admin')");
        $statement->execute(['admin', 'Admin User', $initialEmail, $passwordHash]);
    } else {
        $statement = $pdo->prepare("INSERT INTO users (fullname, email, password, role) VALUES (?, ?, ?, 'admin')");
        $statement->execute(['Admin User', $initialEmail, $passwordHash]);
    }

    return true;
}

function ensureLoginAttemptsTable(PDO $pdo): void
{
    static $tableChecked = false;
    if ($tableChecked) {
        return;
    }

    try {
        $pdo->query('SELECT 1 FROM auth_login_attempts LIMIT 0');
    } catch (PDOException $error) {
        $pdo->exec("CREATE TABLE IF NOT EXISTS auth_login_attempts (
            attempt_key CHAR(64) NOT NULL PRIMARY KEY,
            failed_attempts SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            window_started_at BIGINT UNSIGNED NOT NULL,
            locked_until BIGINT UNSIGNED NOT NULL DEFAULT 0,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_auth_login_updated (updated_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    $tableChecked = true;
}

function getLoginAttemptKey(string $identity): string
{
    $clientIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    if (getenv('TRUST_CLOUDFLARE_CONNECTING_IP') === '1') {
        $forwardedIp = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? '';
        if (filter_var($forwardedIp, FILTER_VALIDATE_IP)) {
            $clientIp = $forwardedIp;
        }
    }

    return hash('sha256', strtolower(trim($identity)) . "\0" . $clientIp);
}

function getLoginLockoutRemaining(PDO $pdo, string $attemptKey, int $failureWindowSeconds = LOGIN_FAILURE_WINDOW_SECONDS): int
{
    ensureLoginAttemptsTable($pdo);

    $statement = $pdo->prepare('SELECT window_started_at, locked_until FROM auth_login_attempts WHERE attempt_key = ?');
    $statement->execute([$attemptKey]);
    $attempt = $statement->fetch(PDO::FETCH_ASSOC);
    if (!$attempt) {
        return 0;
    }

    $now = time();
    $remaining = (int)$attempt['locked_until'] - $now;
    if ($remaining > 0) {
        return $remaining;
    }

    if ((int)$attempt['locked_until'] > 0 || $now - (int)$attempt['window_started_at'] >= $failureWindowSeconds) {
        $statement = $pdo->prepare('DELETE FROM auth_login_attempts WHERE attempt_key = ?');
        $statement->execute([$attemptKey]);
    }

    return 0;
}

function recordLoginFailure(
    PDO $pdo,
    string $attemptKey,
    int $failureLimit = LOGIN_FAILURE_LIMIT,
    int $failureWindowSeconds = LOGIN_FAILURE_WINDOW_SECONDS,
    int $lockoutSeconds = LOGIN_LOCKOUT_SECONDS
): int
{
    ensureLoginAttemptsTable($pdo);
    $now = time();

    $pdo->beginTransaction();
    try {
        $statement = $pdo->prepare('INSERT IGNORE INTO auth_login_attempts (attempt_key, window_started_at) VALUES (?, ?)');
        $statement->execute([$attemptKey, $now]);

        $statement = $pdo->prepare('SELECT failed_attempts, window_started_at, locked_until FROM auth_login_attempts WHERE attempt_key = ? FOR UPDATE');
        $statement->execute([$attemptKey]);
        $attempt = $statement->fetch(PDO::FETCH_ASSOC);

        $failures = (int)$attempt['failed_attempts'];
        $windowStartedAt = (int)$attempt['window_started_at'];
        $lockedUntil = (int)$attempt['locked_until'];

        if ($lockedUntil > $now) {
            $pdo->commit();
            return $lockedUntil - $now;
        }

        if ($lockedUntil > 0 || $now - $windowStartedAt >= $failureWindowSeconds) {
            $failures = 0;
            $windowStartedAt = $now;
        }

        $failures++;
        $lockedUntil = $failures >= $failureLimit ? $now + $lockoutSeconds : 0;

        $statement = $pdo->prepare('UPDATE auth_login_attempts SET failed_attempts = ?, window_started_at = ?, locked_until = ? WHERE attempt_key = ?');
        $statement->execute([$failures, $windowStartedAt, $lockedUntil, $attemptKey]);
        $pdo->commit();

        return max(0, $lockedUntil - $now);
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $error;
    }
}

function clearLoginFailures(PDO $pdo, string $attemptKey): void
{
    ensureLoginAttemptsTable($pdo);
    $statement = $pdo->prepare('DELETE FROM auth_login_attempts WHERE attempt_key = ?');
    $statement->execute([$attemptKey]);
}

function respondLoginLocked(int $seconds): void
{
    http_response_code(429);
    header('Retry-After: ' . $seconds);
    echo json_encode([
        'success' => false,
        'message' => 'Too many failed login attempts. Try again in ' . ceil($seconds / 60) . ' minutes.',
        'retry_after' => $seconds,
    ]);
    exit;
}

function verifyAndUpgradePassword(PDO $pdo, int $userId, string $storedPassword, string $submittedPassword): bool
{
    $passwordInfo = password_get_info($storedPassword);
    $isHashed = isset($passwordInfo['algo']) && $passwordInfo['algo'] !== null;

    if ($isHashed) {
        if (!password_verify($submittedPassword, $storedPassword)) {
            return false;
        }

        if (password_needs_rehash($storedPassword, PASSWORD_DEFAULT)) {
            $newHash = password_hash($submittedPassword, PASSWORD_DEFAULT);
            $statement = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
            $statement->execute([$newHash, $userId]);
        }

        return true;
    }

    if (!hash_equals($storedPassword, $submittedPassword)) {
        return false;
    }

    $newHash = password_hash($submittedPassword, PASSWORD_DEFAULT);
    $statement = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
    $statement->execute([$newHash, $userId]);

    return true;
}

function startAdminSession(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function requireAdminSession(): void
{
    startAdminSession();
    if (empty($_SESSION['admin_user_id'])) {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Admin login required']);
        exit;
    }
}