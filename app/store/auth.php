<?php

function store_refresh_authenticated_user(DatabaseConnection $conn): void
{
    if (!isset($_SESSION['user_id'])) {
        unset($_SESSION['user_role'], $_SESSION['user_email']);
        return;
    }

    $userId = (int) $_SESSION['user_id'];
    $statement = $conn->prepare('SELECT username, email, user_role FROM user_tbl WHERE user_id = ? LIMIT 1');
    $statement->bind_param('i', $userId);
    $statement->execute();
    $user = $statement->get_result()->fetch_assoc();
    $statement->close();

    if (!$user) {
        unset($_SESSION['user_id'], $_SESSION['username'], $_SESSION['user_email'], $_SESSION['user_role']);
        session_regenerate_id(true);
        return;
    }

    $_SESSION['username'] = $user['username'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role'] = $user['user_role'];
}

function store_has_role(string $role): bool
{
    return isset($_SESSION['user_id'], $_SESSION['user_role'])
        && hash_equals($role, (string) $_SESSION['user_role']);
}

function store_login_rate_limit_key(): string
{
    $remoteAddress = $_SERVER['REMOTE_ADDR'] ?? '';
    $packedAddress = is_string($remoteAddress) ? inet_pton($remoteAddress) : false;
    $clientAddress = $packedAddress === false ? 'unknown' : inet_ntop($packedAddress);
    return hash('sha256', 'login:' . $clientAddress);
}

function store_login_rate_limit_retry_after(DatabaseConnection $conn): int
{
    $rateKey = store_login_rate_limit_key();

    if (random_int(1, 100) === 1) {
        $conn->query(
            "DELETE FROM login_rate_limits
             WHERE window_started_at < CURRENT_TIMESTAMP - INTERVAL '1 day'"
        );
    }

    $statement = $conn->prepare(
        "INSERT INTO login_rate_limits (rate_key, window_started_at, attempts)
         VALUES (?, CURRENT_TIMESTAMP, 0)
         ON CONFLICT (rate_key) DO UPDATE
         SET attempts = CASE
                 WHEN login_rate_limits.attempts >= 5
                      AND login_rate_limits.window_started_at <= CURRENT_TIMESTAMP - INTERVAL '1 minute' THEN 0
                 ELSE login_rate_limits.attempts
             END,
             window_started_at = CASE
                 WHEN login_rate_limits.attempts >= 5
                      AND login_rate_limits.window_started_at <= CURRENT_TIMESTAMP - INTERVAL '1 minute' THEN CURRENT_TIMESTAMP
                 ELSE login_rate_limits.window_started_at
             END
         RETURNING CASE
                       WHEN attempts >= 5 THEN GREATEST(
                           1,
                           CEIL(EXTRACT(EPOCH FROM (
                               window_started_at + INTERVAL '1 minute' - CURRENT_TIMESTAMP
                           )))
                       )
                       ELSE 0
                   END AS retry_after"
    );
    $statement->bind_param('s', $rateKey);
    $statement->execute();
    $result = $statement->get_result()->fetch_assoc();
    $statement->close();

    if (!$result) {
        throw new RuntimeException('The login rate limit could not be checked.');
    }

    return (int) $result['retry_after'];
}

function store_record_login_failure(DatabaseConnection $conn): int
{
    $rateKey = store_login_rate_limit_key();
    $statement = $conn->prepare(
        "UPDATE login_rate_limits
         SET attempts = CASE
                 WHEN attempts >= 5 AND window_started_at <= CURRENT_TIMESTAMP - INTERVAL '1 minute' THEN 1
                 ELSE attempts + 1
             END,
             window_started_at = CASE
                 WHEN attempts >= 5 AND window_started_at <= CURRENT_TIMESTAMP - INTERVAL '1 minute' THEN CURRENT_TIMESTAMP
                 WHEN attempts = 4 THEN CURRENT_TIMESTAMP
                 ELSE window_started_at
             END
         WHERE rate_key = ?
         RETURNING CASE
                       WHEN attempts >= 5 THEN GREATEST(
                           1,
                           CEIL(EXTRACT(EPOCH FROM (
                               window_started_at + INTERVAL '1 minute' - CURRENT_TIMESTAMP
                           )))
                       )
                       ELSE 0
                   END AS retry_after"
    );
    $statement->bind_param('s', $rateKey);
    $statement->execute();
    $result = $statement->get_result()->fetch_assoc();
    $statement->close();

    if (!$result) {
        throw new RuntimeException('The failed sign-in attempt could not be recorded.');
    }

    return (int) $result['retry_after'];
}

function store_reset_login_rate_limit(DatabaseConnection $conn): void
{
    $rateKey = store_login_rate_limit_key();
    $statement = $conn->prepare(
        'UPDATE login_rate_limits SET attempts = 0, window_started_at = CURRENT_TIMESTAMP WHERE rate_key = ?'
    );
    $statement->bind_param('s', $rateKey);
    $statement->execute();
    $statement->close();
}
