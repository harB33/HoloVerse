<?php
$pageTitle = $pageTitle ?? 'HoloVerse';
$siteRoot = $siteRoot ?? '';
$pageScriptName = basename((string) ($_SERVER['SCRIPT_NAME'] ?? $_SERVER['PHP_SELF'] ?? ''));
if ($pageScriptName === '') {
    $pageScriptName = basename((string) ($_SERVER['SCRIPT_FILENAME'] ?? ''));
}
$pageLayout = $pageLayout ?? match ($pageScriptName) {
    'index.php' => 'home',
    'products.php', 'product.php', 'pre_order.php', 'talent-merch.php', 'flash_sale.php' => 'catalog',
    'cart.php', 'checkout.php' => 'commerce',
    'login.php', 'logout.php' => 'account',
    'admin-dashboard.php', 'inventory.php', 'product-input.php', 'flash-sale-admin.php',
    'temp-import-official-catalog.php' => 'admin',
    'about-us.php', 'collector_showcase.php', 'personalized.php', 'talents.php' => 'editorial',
    default => 'content',
};
?>
<!DOCTYPE html>
<html lang="en" data-page-layout="<?= htmlspecialchars($pageLayout, ENT_QUOTES, 'UTF-8') ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        html[data-page-layout="home"]::before,
        html[data-page-layout="home"]::after {
            position: fixed;
            content: "";
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
            transition: opacity 450ms ease, visibility 0s linear 450ms;
        }

        html[data-page-layout="home"]::before {
            z-index: 99998;
            inset: 0;
            background-color: #fff;
            background-image:
                radial-gradient(circle at center, transparent 0 112px, rgb(28 167 198 / 5%) 113px 114px, transparent 115px 162px, rgb(28 167 198 / 4%) 163px 164px, transparent 165px),
                repeating-conic-gradient(from 8deg at 50% 50%, transparent 0deg 28deg, rgb(28 167 198 / 2%) 29deg 30deg, transparent 31deg 60deg);
        }

        html[data-page-layout="home"]::after {
            z-index: 99999;
            top: 50%;
            left: 50%;
            width: 360px;
            height: 125px;
            background-image: url("<?= htmlspecialchars($siteRoot, ENT_QUOTES, 'UTF-8') ?>assets/images/holoverse-wordmark-dark.png");
            background-position: center;
            background-repeat: no-repeat;
            background-size: 340px auto;
            transform: translate(-50%, -50%);
        }

        html[data-page-layout="home"].page-loading {
            overflow: hidden;
        }

        html[data-page-layout="home"].page-loading::before {
            opacity: 1;
            visibility: visible;
            pointer-events: all;
            transition: opacity 450ms ease, visibility 0s;
        }

        html[data-page-layout="home"].page-loading::after {
            opacity: 1;
            visibility: visible;
            animation: page-loader-pulse 2.5s ease-in-out infinite alternate;
            transition: opacity 450ms ease, visibility 0s;
        }

        @keyframes page-loader-pulse {
            from {
                filter: blur(0);
                transform: translate(-50%, -50%) scale(.96);
            }

            to {
                filter: blur(.35px);
                transform: translate(-50%, -50%) scale(1.04);
            }
        }

        <?php if (in_array($pageLayout, ['purchase', 'purchase-detail'], true)): ?>
        @view-transition {
            navigation: none;
        }
        <?php elseif ($pageLayout !== 'home'): ?>
        @view-transition {
            navigation: auto;
        }
        <?php endif; ?>

        ::view-transition-old(root) {
            animation: page-transition-out 450ms ease both;
        }

        ::view-transition-new(root) {
            animation: page-transition-in 750ms cubic-bezier(.2, .75, .25, 1) both;
        }

        html.page-enter body > :not(script):not(style) {
            animation: page-opening 750ms cubic-bezier(.2, .75, .25, 1) both;
        }

        @keyframes page-transition-out {
            to {
                opacity: 0;
                transform: translateY(-8px);
            }
        }

        @keyframes page-transition-in {
            from {
                opacity: 0;
                transform: translateY(8px);
            }
        }

        @keyframes page-opening {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            html[data-page-layout="home"]::before,
            html[data-page-layout="home"]::after,
            ::view-transition-old(root),
            ::view-transition-new(root),
            html[data-page-layout="home"].page-loading::after,
            html.page-enter body > :not(script):not(style) {
                animation: none;
                transition: none;
            }
        }

        html[data-reduce-motion="true"][data-page-layout="home"]::before,
        html[data-reduce-motion="true"][data-page-layout="home"]::after,
        html[data-reduce-motion="true"]::view-transition-old(root),
        html[data-reduce-motion="true"]::view-transition-new(root) {
            animation: none;
            transition: none;
        }
    </style>
    <script>
        (() => {
            const pageRoot = document.documentElement;
            const hasHomeLoader = pageRoot.dataset.pageLayout === "home";
            const reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
            const supportsPageTransitions = CSS.supports("view-transition-name: root");
            const disablePageTransitions = ["purchase", "purchase-detail"].includes(pageRoot.dataset.pageLayout);
            const cameFromSameOrigin = document.referrer !== "" &&
                new URL(document.referrer).origin === window.location.origin;
            const shouldAnimateOpening = !disablePageTransitions && (!cameFromSameOrigin || !supportsPageTransitions);

            const startOpening = () => {
                if (
                    reducedMotion ||
                    pageRoot.dataset.reduceMotion === "true" ||
                    !shouldAnimateOpening
                ) {
                    pageRoot.removeAttribute("aria-busy");
                    return;
                }
                pageRoot.classList.add("page-enter");
                window.setTimeout(() => pageRoot.classList.remove("page-enter"), 600);
                pageRoot.removeAttribute("aria-busy");
            };

            if (hasHomeLoader) {
                pageRoot.classList.add("page-loading");
                pageRoot.setAttribute("aria-busy", "true");

                window.addEventListener("load", () => {
                    window.setTimeout(() => {
                        pageRoot.classList.remove("page-loading");
                        pageRoot.removeAttribute("aria-busy");
                    }, reducedMotion ? 0 : Math.max(0, 3000 - performance.now()));
                }, { once: true });
                return;
            }

            startOpening();
        })();
    </script>
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="icon" type="image/png" href="<?= $siteRoot ?>assets/images/logo.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@400;500;600;700;800&family=Montserrat:wght@500;600;700;800;900&family=Noto+Sans+JP:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= $siteRoot ?>assets/css/tailwind.css?v=<?= filemtime(__DIR__ . '/../../../assets/css/tailwind.css') ?>">
    <script src="<?= $siteRoot ?>assets/js/catalog-search-suggestions.js" defer></script>
    <script src="<?= $siteRoot ?>assets/js/collector-favorites.js?v=<?= filemtime(__DIR__ . '/../../../assets/js/collector-favorites.js') ?>" defer></script>
    <script>
        document.addEventListener("keydown", (event) => {
            if (!(event.ctrlKey || event.metaKey) || event.key.toLowerCase() !== "a") {
                return;
            }

            const target = event.target;
            if (
                target instanceof HTMLInputElement ||
                target instanceof HTMLTextAreaElement ||
                (target instanceof HTMLElement && target.isContentEditable)
            ) {
                return;
            }

            event.preventDefault();
            window.getSelection()?.removeAllRanges();
        }, true);
    </script>
</head>
