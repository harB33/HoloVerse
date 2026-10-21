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

