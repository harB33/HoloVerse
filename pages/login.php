<?php
require_once __DIR__ . '/../app/store.php';

$isRegistration = ($_GET['mode'] ?? '') === 'register' || ($_POST['mode'] ?? '') === 'register';
$message = '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!store_verify_csrf()) {
        http_response_code(403);
        $message = 'Your session expired. Refresh the page and try again.';
    } else {
        $emailInput = $_POST['email'] ?? '';
        $passwordInput = $_POST['password'] ?? '';
        $email = is_string($emailInput) ? strtolower(trim($emailInput)) : '';
        $password = is_string($passwordInput) ? $passwordInput : '';

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 100 || $password === '') {
            $message = 'Enter a valid email and password.';
        } elseif ($isRegistration) {
            $usernameInput = $_POST['username'] ?? '';
            $username = is_string($usernameInput) ? trim($usernameInput) : '';
            if (strlen($username) < 2 || strlen($username) > 50 || strlen($password) < 8 || strlen($password) > 72) {
                $message = 'Choose a name between 2 and 50 characters and a password between 8 and 72 characters.';
            } else {
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                $statement = $conn->prepare('INSERT INTO user_tbl (username, password_hash, email) VALUES (?, ?, ?)');
                $statement->bind_param('sss', $username, $passwordHash, $email);
                $registeredUserId = 0;
                try {
                    $statement->execute();
                    $registeredUserId = (int) $conn->insert_id;
                } catch (mysqli_sql_exception $error) {
                    if ($error->getCode() !== 1062) {
                        throw $error;
                    }
                    $message = 'An account with this email already exists.';
                } finally {
                    $statement->close();
                }
                if ($registeredUserId > 0) {
                    $_SESSION['user_id'] = $registeredUserId;
                    $_SESSION['username'] = $username;
                    $_SESSION['user_role'] = 'user';
                    session_regenerate_id(true);
                    store_merge_guest_cart($conn, $registeredUserId);
                    header('Location: cart.php');
                    exit;
                }
            }
        } else {
            $statement = $conn->prepare('SELECT user_id, username, password_hash, user_role FROM user_tbl WHERE email = ? LIMIT 1');
            $statement->bind_param('s', $email);
            $statement->execute();
            $user = $statement->get_result()->fetch_assoc();
            $statement->close();

            if (!$user || !password_verify($password, $user['password_hash'])) {
                $message = 'Email or password is incorrect.';
            } else {
                session_regenerate_id(true);
                $_SESSION['user_id'] = (int) $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_role'] = $user['user_role'];
                store_merge_guest_cart($conn, (int) $user['user_id']);
                header('Location: cart.php');
                exit;
            }
        }
    }
}

$pageTitle = $isRegistration ? 'Create Account | HoloVerse' : 'Sign In | HoloVerse';
$siteRoot = '../';
require __DIR__ . '/../app/views/layout/head.php';
?>

<body class="min-h-screen border-t-2 border-sky-500 bg-slate-100 font-['Kanit'] text-slate-800">
    <main class="flex min-h-screen w-full items-center justify-center px-4 py-8 sm:px-8 lg:px-12">
        <div class="grid min-h-[720px] w-full max-w-7xl grid-cols-1 overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-xl shadow-slate-300/40 lg:grid-cols-2">
            <section class="flex flex-col justify-center py-10 lg:pl-12 xl:pl-20">
                <div class="w-full max-w-md">
                    <header class="mb-8">
                        <h1 class="text-3xl font-bold text-slate-800"><?= $isRegistration ? 'Create your account' : 'Welcome back' ?></h1>
                        <p class="mt-4 max-w-sm text-sm leading-6 text-slate-600 sm:text-base">
                            <?= $isRegistration ? 'Create an account to keep your cart with you.' : 'Sign in to manage your HoloVerse account and saved cart.' ?>
                        </p>
                    </header>

                    <?php if ($message !== ''): ?>
                        <p class="mb-5 rounded-lg bg-rose-50 px-4 py-3 text-sm text-rose-700" role="alert"><?= store_escape($message) ?></p>
                    <?php endif; ?>

                    <form action="login.php<?= $isRegistration ? '?mode=register' : '' ?>" method="post" class="space-y-5">
                        <input type="hidden" name="csrf_token" value="<?= store_escape(store_csrf_token()) ?>">
                        <input type="hidden" name="mode" value="<?= $isRegistration ? 'register' : 'login' ?>">
                        <?php if ($isRegistration): ?>
                            <div>
                                <label for="username" class="mb-2 block text-sm font-medium text-slate-700">Name</label>
                                <input id="username" type="text" name="username" autocomplete="name" minlength="2" maxlength="50" required
                                    class="block w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100">
                            </div>
                        <?php endif; ?>
                        <div>
                            <label for="email" class="mb-2 block text-sm font-medium text-slate-700">Email</label>
                            <input id="email" type="email" name="email" autocomplete="email" placeholder="Example@email.com" required
                                class="block w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-sky-400 focus:ring-4 focus:ring-sky-100">
                        </div>

                        <div>
                            <label for="password" class="mb-2 block text-sm font-medium text-slate-700">Password</label>
                            <input id="password" type="password" name="password" autocomplete="<?= $isRegistration ? 'new-password' : 'current-password' ?>"
                                placeholder="<?= $isRegistration ? '8–72 characters' : 'Your password' ?>" minlength="<?= $isRegistration ? 8 : 1 ?>" <?= $isRegistration ? 'maxlength="72"' : '' ?> required
                                class="block w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-sky-400 focus:ring-4 focus:ring-sky-100">
                        </div>

                        <button type="submit" class="w-full rounded-lg bg-slate-800 px-5 py-3 text-center text-base font-medium text-white shadow-sm transition hover:bg-slate-900 focus:outline-none focus:ring-4 focus:ring-slate-200">
                            <?= $isRegistration ? 'Create account' : 'Sign in' ?>
                        </button>
                    </form>

                    <p class="mt-9 text-center text-sm text-slate-600">
                        <?= $isRegistration ? 'Already have an account?' : 'Don’t have an account?' ?>
                        <a href="login.php<?= $isRegistration ? '' : '?mode=register' ?>" class="font-medium text-blue-600 transition hover:text-blue-800">
                            <?= $isRegistration ? 'Sign in' : 'Create one' ?>
                        </a>
                    </p>
                </div>
            </section>

            <aside class="relative min-h-[420px] overflow-hidden rounded-2xl shadow-sm sm:min-h-[560px] lg:min-h-full" aria-label="Decorative artwork">
                <img src="../assets/images/suisei2.jpg" alt="" class="absolute inset-0 h-full w-full object-cover object-center">
            </aside>
        </div>
    </main>
</body>

</html>
