<?php
require_once __DIR__ . '/../app/store.php';

$isRegistration = ($_GET['mode'] ?? '') === 'register' || ($_POST['mode'] ?? '') === 'register';
$message = '';
$registrationName = '';
$registrationEmail = '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!store_verify_csrf()) {
        http_response_code(403);
        $message = 'Your session expired. Refresh the page and try again.';
    } else {
        $emailInput = $_POST['email'] ?? '';
        $passwordInput = $_POST['password'] ?? '';
        $email = is_string($emailInput) ? strtolower(trim($emailInput)) : '';
        $password = is_string($passwordInput) ? $passwordInput : '';
        $registrationEmail = $isRegistration ? $email : '';
        $usernameInput = $_POST['username'] ?? '';
        $registrationName = is_string($usernameInput) ? trim($usernameInput) : '';

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 100 || $password === '') {
            $message = 'Enter a valid email and password.';
        } elseif ($isRegistration) {
            $username = $registrationName;
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
        <div class="auth-layout<?= $isRegistration ? ' is-register' : '' ?> relative grid min-h-[720px] w-full max-w-7xl grid-cols-1 overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-xl shadow-slate-300/40 lg:grid-cols-2">
            <section class="auth-login-panel flex flex-col justify-center py-10 lg:pl-12 xl:pl-20" aria-label="Sign in" aria-hidden="<?= $isRegistration ? 'true' : 'false' ?>"<?= $isRegistration ? ' inert' : '' ?>>
                <div class="w-full max-w-md">
                    <header class="mb-8">
                        <h1 class="text-3xl font-bold text-slate-800">Welcome back</h1>
                        <p class="mt-4 max-w-sm text-sm leading-6 text-slate-600 sm:text-base">Sign in to manage your HoloVerse account and saved cart.</p>
                    </header>

                    <?php if ($message !== '' && !$isRegistration): ?>
                        <p class="mb-5 rounded-lg bg-rose-50 px-4 py-3 text-sm text-rose-700" role="alert"><?= store_escape($message) ?></p>
                    <?php endif; ?>

                    <form action="login.php" method="post" class="space-y-5">
                        <input type="hidden" name="csrf_token" value="<?= store_escape(store_csrf_token()) ?>">
                        <input type="hidden" name="mode" value="login">
                        <div>
                            <label for="login-email" class="mb-2 block text-sm font-medium text-slate-700">Email</label>
                            <input id="login-email" type="email" name="email" autocomplete="email" placeholder="Example@email.com" required
                                class="block w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-sky-400 focus:ring-4 focus:ring-sky-100">
                        </div>
                        <div>
                            <label for="login-password" class="mb-2 block text-sm font-medium text-slate-700">Password</label>
                            <input id="login-password" type="password" name="password" autocomplete="current-password" placeholder="Your password" required
                                class="block w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-sky-400 focus:ring-4 focus:ring-sky-100">
                        </div>
                        <button type="submit" class="w-full rounded-lg bg-slate-800 px-5 py-3 text-center text-base font-medium text-white shadow-sm transition hover:bg-slate-900 focus:outline-none focus:ring-4 focus:ring-slate-200">
                            Sign in
                        </button>
                    </form>

                    <p class="mt-9 text-center text-sm text-slate-600">
                        Don’t have an account?
                        <button type="button" class="auth-mode-button" data-show-register>Create account</button>
                    </p>
                </div>
            </section>

            <aside class="auth-artwork-panel relative min-h-[420px] overflow-hidden rounded-2xl shadow-sm sm:min-h-[560px] lg:min-h-full" aria-label="Create account">
                <img src="../assets/images/suisei2.jpg" alt="" class="absolute inset-0 h-full w-full object-cover object-center">
            </aside>
                <section class="auth-register-surface" aria-label="Create account" aria-hidden="<?= $isRegistration ? 'false' : 'true' ?>"<?= $isRegistration ? '' : ' inert' ?>>
                    <div class="auth-register-content">
                        <header class="mb-8">
                            <h2 class="text-3xl font-bold text-slate-800">Create your account</h2>
                            <p class="mt-4 text-sm leading-6 text-slate-600">Create an account to keep your cart with you.</p>
                        </header>

                        <?php if ($message !== '' && $isRegistration): ?>
                            <p class="mb-5 rounded-lg bg-rose-50 px-4 py-3 text-sm text-rose-700" role="alert"><?= store_escape($message) ?></p>
                        <?php endif; ?>

                        <form action="login.php?mode=register" method="post" class="space-y-5">
                            <input type="hidden" name="csrf_token" value="<?= store_escape(store_csrf_token()) ?>">
                            <input type="hidden" name="mode" value="register">
                            <div>
                                <label for="register-username" class="mb-2 block text-sm font-medium text-slate-700">Name</label>
                                <input id="register-username" type="text" name="username" autocomplete="name" minlength="2" maxlength="50" required value="<?= store_escape($registrationName) ?>"
                                    class="block w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100">
                            </div>
                            <div>
                                <label for="register-email" class="mb-2 block text-sm font-medium text-slate-700">Email</label>
                                <input id="register-email" type="email" name="email" autocomplete="email" placeholder="Example@email.com" required value="<?= store_escape($registrationEmail) ?>"
                                    class="block w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-sky-400 focus:ring-4 focus:ring-sky-100">
                            </div>
                            <div>
                                <label for="register-password" class="mb-2 block text-sm font-medium text-slate-700">Password</label>
                                <input id="register-password" type="password" name="password" autocomplete="new-password" placeholder="8–72 characters" minlength="8" maxlength="72" required
                                    class="block w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-sky-400 focus:ring-4 focus:ring-sky-100">
                            </div>
                            <button type="submit" class="w-full rounded-lg bg-slate-800 px-5 py-3 text-center text-base font-medium text-white shadow-sm transition hover:bg-slate-900 focus:outline-none focus:ring-4 focus:ring-slate-200">
                                Create account
                            </button>
                        </form>
                        <p class="mt-6 text-center text-sm text-slate-600">
                            Already have an account?
                            <button type="button" class="auth-mode-button" data-show-login>Sign in</button>
                        </p>
                    </div>
                </section>
        </div>
    </main>
    <script>
        const authShell = document.querySelector(".auth-layout");
        const loginPanel = document.querySelector(".auth-login-panel");
        const registerPanel = document.querySelector(".auth-register-surface");
        const setAuthMode = (showRegister) => {
            authShell.classList.toggle("is-register", showRegister);
            loginPanel.setAttribute("aria-hidden", String(showRegister));
            loginPanel.inert = showRegister;
            registerPanel.setAttribute("aria-hidden", String(!showRegister));
            registerPanel.inert = !showRegister;
            if (showRegister) {
                document.querySelector("#register-username").focus();
            } else {
                document.querySelector("#login-email").focus();
            }
        };

        document.querySelectorAll("[data-show-register]").forEach((button) => {
            button.addEventListener("click", () => {
                window.history.pushState({ authMode: "register" }, "", "?mode=register");
                setAuthMode(true);
            });
        });
        document.querySelectorAll("[data-show-login]").forEach((button) => {
            button.addEventListener("click", () => {
                window.history.pushState({ authMode: "login" }, "", window.location.pathname);
                setAuthMode(false);
            });
        });
        window.addEventListener("popstate", () => setAuthMode(new URLSearchParams(window.location.search).get("mode") === "register"));
    </script>
</body>

</html>
