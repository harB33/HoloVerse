<?php
$pageTitle = 'Sign In | HoloVerse';
$siteRoot = '../';
require __DIR__ . '/../app/views/layout/head.php';
?>

<body class="min-h-screen border-t-2 border-sky-500 bg-slate-100 font-['Kanit'] text-slate-800">
    <main class="flex min-h-screen w-full items-center justify-center px-4 py-8 sm:px-8 lg:px-12">
        <div class="grid min-h-[720px] w-full max-w-7xl grid-cols-1 overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-xl shadow-slate-300/40 lg:grid-cols-2">
            <section class="flex flex-col justify-center py-10 lg:pl-12 xl:pl-20">
                <div class="w-full max-w-md">
                    <header class="mb-8">
                        <p class="mt-4 max-w-sm text-sm leading-6 text-slate-600 sm:text-base">
                            Today is a new day. It's your day. You shape it.<br>
                            Sign in to start managing your projects.
                        </p>
                    </header>

                    <form action="#" method="post" class="space-y-5">
                        <div>
                            <label for="email" class="mb-2 block text-sm font-medium text-slate-700">Email</label>
                            <input
                                id="email"
                                type="email"
                                name="email"
                                autocomplete="email"
                                placeholder="Example@email.com"
                                required
                                class="block w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-sky-400 focus:ring-4 focus:ring-sky-100">
                        </div>

                        <div>
                            <div class="mb-2 flex items-center justify-between gap-4">
                                <label for="password" class="block text-sm font-medium text-slate-700">Password</label>
                            </div>
                            <input
                                id="password"
                                type="password"
                                name="password"
                                autocomplete="current-password"
                                placeholder="At least 8 characters"
                                minlength="8"
                                required
                                class="block w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-sky-400 focus:ring-4 focus:ring-sky-100">
                            <a href="#" class="mt-3 block text-right text-sm font-medium text-blue-600 transition hover:text-blue-800">Forgot Password?</a>
                        </div>

                        <button
                            type="submit"
                            class="w-full rounded-lg bg-slate-800 px-5 py-3 text-center text-base font-medium text-white shadow-sm transition hover:bg-slate-900 focus:outline-none focus:ring-4 focus:ring-slate-200">
                            Sign in
                        </button>
                    </form>

                    <div class="my-9 flex items-center gap-3 text-xs text-slate-500">
                        <span class="h-px flex-1 bg-slate-200"></span>
                        <span>Or</span>
                        <span class="h-px flex-1 bg-slate-200"></span>
                    </div>

                    <div class="space-y-3">
                        <a href="#" class="flex min-h-11 w-full items-center justify-center gap-3 rounded-lg bg-slate-50 px-4 py-3 text-sm font-medium text-slate-600 transition hover:bg-slate-100">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-mail preview-icon">
                                <path d="m22 7-8.991 5.727a2 2 0 0 1-2.009 0L2 7" />
                                <rect x="2" y="4" width="20" height="16" rx="2" />
                            </svg>
                            <span>Sign in with Google</span>
                        </a>
                        <a href="#" class="flex min-h-11 w-full items-center justify-center gap-3 rounded-lg bg-slate-50 px-4 py-3 text-sm font-medium text-slate-600 transition hover:bg-slate-100">
                            <span class="flex h-5 w-5 items-center justify-center rounded-full bg-blue-600 text-sm font-bold text-white" aria-hidden="true">f</span>
                            <span>Sign in with Facebook</span>
                        </a>
                    </div>

                    <p class="mt-9 text-center text-sm text-slate-600">
                        Don't you have an account?
                        <a href="#" class="font-medium text-blue-600 transition hover:text-blue-800">Sign up</a>
                    </p>
                </div>

                <p class="mt-20 text-center text-xs font-medium uppercase text-slate-400 lg:mt-24 lg:text-left xl:pl-12">
                    © 2023 All rights reserved
                </p>
            </section>

            <aside class="relative min-h-[420px] overflow-hidden rounded-2xl shadow-sm sm:min-h-[560px] lg:min-h-full" aria-label="Decorative floral artwork">
                <img
                    src="../assets/images/suisei2.jpg"
                    alt=""
                    class="absolute inset-0 h-full w-full object-cover object-center">
            </aside>
        </div>
    </main>
    <?php
    $cartHref = 'cart.php';
    include __DIR__ . '/../app/views/components/cart-button.php';
    ?>
</body>

</html>