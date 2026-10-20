<?php
$pageTitle = $pageTitle ?? 'HoloVerse';
$siteRoot = $siteRoot ?? '';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php if (!isset($showPageLoader) || $showPageLoader): ?>
    <style>
        html::before,
        html::after {
            position: fixed;
            content: "";
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
            transition: opacity 450ms ease, visibility 0s linear 450ms;
        }

        html::before {
            z-index: 99998;
            inset: 0;
            background-color: #fff;
            background-image:
                radial-gradient(circle at center, transparent 0 112px, rgb(28 167 198 / 5%) 113px 114px, transparent 115px 162px, rgb(28 167 198 / 4%) 163px 164px, transparent 165px),
                repeating-conic-gradient(from 8deg at 50% 50%, transparent 0deg 28deg, rgb(28 167 198 / 2%) 29deg 30deg, transparent 31deg 60deg);
        }

        html::after {
            z-index: 99999;
            top: 50%;
            left: 50%;
            width: 360px;
            height: 125px;
            background-image: url("<?= htmlspecialchars($siteRoot, ENT_QUOTES, 'UTF-8') ?>assets/images/hololive.png");
            background-position: center;
            background-repeat: no-repeat;
            background-size: 340px auto;
            transform: translate(-50%, -50%);
        }

        html.page-loading {
            overflow: hidden;
        }

        html.page-loading::before {
            opacity: 1;
            visibility: visible;
            pointer-events: all;
            transition: opacity 450ms ease, visibility 0s;
        }

        html.page-loading::after {
            opacity: 1;
            visibility: visible;
            animation: page-loader-pulse 1.5s ease-in-out infinite alternate;
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

        @media (prefers-reduced-motion: reduce) {
            html::before,
            html::after {
                transition: none;
            }

            html.page-loading::after {
                animation: none;
            }
        }
    </style>
    <script>
        (() => {
            const pageRoot = document.documentElement;
            const minimumVisibleDuration = 1800;
            const loadingStartedAt = performance.now();

            pageRoot.classList.add("page-loading");
            pageRoot.setAttribute("aria-busy", "true");

            window.addEventListener("load", () => {
                const remainingDuration = Math.max(
                    0,
                    minimumVisibleDuration - (performance.now() - loadingStartedAt)
                );

                window.setTimeout(() => {
                    pageRoot.classList.add("page-enter");
                    pageRoot.classList.remove("page-loading");
                    pageRoot.removeAttribute("aria-busy");
                }, remainingDuration);
            }, { once: true });
        })();
    </script>
    <?php endif; ?>
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="icon" type="image/png" href="<?= $siteRoot ?>assets/images/logo.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@400;500;600;700;800&family=Montserrat:wght@500;600;700;800;900&family=Noto+Sans+JP:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= $siteRoot ?>assets/css/tailwind.css?v=<?= filemtime(__DIR__ . '/../../../assets/css/tailwind.css') ?>">
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
