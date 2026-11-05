<?php
$pageTitle = $pageTitle ?? 'HoloVerse';
$siteRoot = $siteRoot ?? '';
$pageScriptName = basename((string) ($_SERVER['SCRIPT_NAME'] ?? $_SERVER['PHP_SELF'] ?? ''));
if ($pageScriptName === '') {
    $pageScriptName = basename((string) ($_SERVER['SCRIPT_FILENAME'] ?? ''));
}
$pageLayout = $pageLayout ?? match ($pageScriptName) {
    'index.php' => 'home',
    'products.php', 'product.php', 'flash_sale.php' => 'catalog',
    'cart.php', 'checkout.php' => 'commerce',
    'login.php', 'logout.php', 'profile.php' => 'account',
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
            ::view-transition-old(root),
            ::view-transition-new(root),
            html.page-enter body > :not(script):not(style) {
                animation: none;
                transition: none;
            }
        }

        html[data-reduce-motion="true"]::view-transition-old(root),
        html[data-reduce-motion="true"]::view-transition-new(root) {
            animation: none;
            transition: none;
        }
    </style>
    <script>
        (() => {
            const pageRoot = document.documentElement;
            const reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
            const supportsPageTransitions = CSS.supports("view-transition-name: root");
            const disablePageTransitions = ["purchase", "purchase-detail"].includes(pageRoot.dataset.pageLayout);
            if (pageRoot.dataset.pageLayout === "home" && !reducedMotion) {
                pageRoot.dataset.homeLoading = "true";
                window.addEventListener("load", () => {
                    pageRoot.dataset.homeLoaderStarted = "true";
                    window.setTimeout(() => {
                        window.dispatchEvent(new Event("home-loader-reveal"));
                    }, 3500);
                    window.setTimeout(() => {
                        delete pageRoot.dataset.homeLoading;
                        delete pageRoot.dataset.homeLoaderStarted;
                    }, 4260);
                }, { once: true });
            }
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

            startOpening();
        })();
    </script>
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="icon" type="image/png" href="<?= $siteRoot ?>assets/images/logo.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@400;500;600;700;800&family=Montserrat:wght@500;600;700;800;900&family=Noto+Sans+JP:wght@400;500;700;900&family=Outfit:wght@500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= $siteRoot ?>assets/css/tailwind.css?v=<?= filemtime(__DIR__ . '/../../../assets/css/tailwind.css') ?>">
    <?php if (isset($pageStylesheet) && is_string($pageStylesheet) && $pageStylesheet !== ''): ?>
        <link rel="stylesheet" href="<?= htmlspecialchars($pageStylesheet, ENT_QUOTES, 'UTF-8') ?>">
    <?php endif; ?>
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
