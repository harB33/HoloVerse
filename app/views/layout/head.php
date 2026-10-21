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
            background-image: url("<?= htmlspecialchars($siteRoot, ENT_QUOTES, 'UTF-8') ?>assets/images/hololive.png");
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

        html.page-enter body > :not(.page-loading-skeleton) {
            animation: page-opening 1200ms cubic-bezier(.2, .75, .25, 1) both;
        }

        @keyframes page-opening {
            from {
                opacity: 0;
                transform: translateY(12px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (prefers-reduced-motion: reduce) {
            html[data-page-layout="home"]::before,
            html[data-page-layout="home"]::after {
                transition: none;
            }

            html[data-page-layout="home"].page-loading::after,
            html.page-enter body > :not(.page-loading-skeleton),
            html.page-transition-loading .page-loading-skeleton__block::after {
                animation: none;
            }
        }

        html.page-transition-loading {
            overflow: hidden;
        }

        html.page-transition-loading body > :not(.page-loading-skeleton) {
            visibility: hidden !important;
        }

        .page-loading-skeleton {
            position: fixed;
            z-index: 99999;
            inset: 0;
            display: none;
            padding: 0;
            overflow: hidden;
            background: transparent;
            pointer-events: all;
            visibility: visible !important;
        }

        html.page-transition-loading .page-loading-skeleton {
            display: block;
        }

        .page-loading-skeleton__block {
            position: fixed;
            overflow: hidden;
            display: block;
            height: 14px;
            border-radius: 6px;
            background-color: #e8eef3;
        }

        .page-loading-skeleton__block--image {
            border-radius: 10px;
        }

        .page-loading-skeleton__block::after {
            position: absolute;
            inset: 0;
            content: "";
            background: linear-gradient(90deg, transparent, rgb(255 255 255 / 95%), transparent);
            transform: translateX(-100%);
            animation: page-skeleton-shimmer 2.5s ease-in-out infinite;
            will-change: transform;
        }

        @keyframes page-skeleton-shimmer {
            50%,
            100% {
                transform: translateX(100%);
            }
        }

    </style>
    <script>
        (() => {
            const pageRoot = document.documentElement;
            const hasHomeLoader = pageRoot.dataset.pageLayout === "home";
            const minimumVisibleDuration = hasHomeLoader ? 2600 : 0;
            const loadingStartedAt = performance.now();
            let initialLoadingTimeout = null;
            let navigationStarted = false;

            const stopPageLoading = () => {
                if (initialLoadingTimeout !== null) {
                    window.clearTimeout(initialLoadingTimeout);
                    initialLoadingTimeout = null;
                }
                pageRoot.classList.remove("page-loading", "page-transition-loading");
                pageRoot.removeAttribute("aria-busy");
                document.querySelector(".page-loading-skeleton")?.remove();
            };

            const intersectRects = (first, second) => {
                const left = Math.max(first.left, second.left);
                const top = Math.max(first.top, second.top);
                const right = Math.min(first.right, second.right);
                const bottom = Math.min(first.bottom, second.bottom);
                return { left, top, right, bottom };
            };

            const getVisibleRect = (element, rect) => {
                let visibleRect = intersectRects(rect, {
                    left: 0,
                    top: 0,
                    right: window.innerWidth,
                    bottom: window.innerHeight,
                });

                for (let ancestor = element.parentElement; ancestor; ancestor = ancestor.parentElement) {
                    const style = window.getComputedStyle(ancestor);
                    if (style.display === "none" || style.visibility === "hidden" || Number(style.opacity) === 0) {
                        return null;
                    }

                    if (/(hidden|clip|auto|scroll)/.test(`${style.overflowX} ${style.overflowY}`)) {
                        const ancestorRect = ancestor.getBoundingClientRect();
                        visibleRect = intersectRects(visibleRect, {
                            left: ancestorRect.left + ancestor.clientLeft,
                            top: ancestorRect.top + ancestor.clientTop,
                            right: ancestorRect.left + ancestor.clientLeft + ancestor.clientWidth,
                            bottom: ancestorRect.top + ancestor.clientTop + ancestor.clientHeight,
                        });
                    }

                    if (visibleRect.right - visibleRect.left < 4 || visibleRect.bottom - visibleRect.top < 4) {
                        return null;
                    }
                }

                return visibleRect;
            };

            const appendBlock = (overlay, rect, sourceElement, modifier = "") => {
                const visibleRect = getVisibleRect(sourceElement, rect);
                if (!visibleRect || visibleRect.right - visibleRect.left < 4 || visibleRect.bottom - visibleRect.top < 4) {
                    return;
                }

                const { left, top, right, bottom } = visibleRect;

                const block = document.createElement("span");
                block.className = `page-loading-skeleton__block${modifier}`;
                block.style.left = `${left}px`;
                block.style.top = `${top}px`;
                block.style.width = `${right - left}px`;
                block.style.height = `${bottom - top}px`;
                overlay.append(block);
            };

            const startPageLoading = () => {
                if (document.querySelector(".page-loading-skeleton")) {
                    return;
                }

                const skeleton = document.createElement("div");
                skeleton.className = "page-loading-skeleton";
                skeleton.setAttribute("aria-hidden", "true");

                const pageContent = document.body;
                const contentSelector = "img, video, input, select, textarea, button, [role='button']";

                pageContent.querySelectorAll(contentSelector).forEach((element) => {
                    if (
                        element.closest("[aria-hidden='true'], script, style, .page-loading-skeleton") ||
                        element.getClientRects().length === 0 ||
                        !element.checkVisibility({ checkOpacity: true, checkVisibilityCSS: true })
                    ) {
                        return;
                    }

                    const modifier = element.matches("img, video")
                        ? " page-loading-skeleton__block--image"
                        : "";
                    appendBlock(skeleton, element.getBoundingClientRect(), element, modifier);
                });

                const textWalker = document.createTreeWalker(pageContent, NodeFilter.SHOW_TEXT, {
                    acceptNode(node) {
                        if (
                            !node.textContent.trim() ||
                            node.parentElement.closest("script, style, svg, [aria-hidden='true'], input, textarea, select, button, .page-loading-skeleton") ||
                            !node.parentElement.checkVisibility({ checkOpacity: true, checkVisibilityCSS: true })
                        ) {
                            return NodeFilter.FILTER_REJECT;
                        }
                        return NodeFilter.FILTER_ACCEPT;
                    },
                });

                const textRects = [];
                let textNode;
                while ((textNode = textWalker.nextNode())) {
                    const range = document.createRange();
                    range.selectNodeContents(textNode);
                    [...range.getClientRects()].forEach((rect) => {
                        const visibleRect = getVisibleRect(textNode.parentElement, rect);
                        if (visibleRect && visibleRect.right - visibleRect.left >= 4 && visibleRect.bottom - visibleRect.top >= 4) {
                            textRects.push({ rect: visibleRect, element: textNode.parentElement });
                        }
                    });
                }

                textRects.sort((first, second) =>
                    first.rect.top - second.rect.top || first.rect.left - second.rect.left
                );
                const textLines = [];
                textRects.forEach(({ rect, element }) => {
                    const line = textLines.find((candidate) =>
                        Math.abs(candidate.top - rect.top) <= 2 &&
                        rect.left <= candidate.right + 6 &&
                        rect.right >= candidate.left - 6
                    );
                    if (line) {
                        line.left = Math.min(line.left, rect.left);
                        line.top = Math.min(line.top, rect.top);
                        line.right = Math.max(line.right, rect.right);
                        line.bottom = Math.max(line.bottom, rect.bottom);
                    } else {
                        textLines.push({
                            left: rect.left,
                            top: rect.top,
                            right: rect.right,
                            bottom: rect.bottom,
                            element,
                        });
                    }
                }
                );
                textLines.forEach(({ left, top, right, bottom, element }) => {
                    appendBlock(skeleton, { left, top, right, bottom }, element);
                });

                document.body.append(skeleton);
                navigationStarted = true;
                pageRoot.classList.remove("page-loading", "page-enter");
                pageRoot.classList.add("page-transition-loading");
                pageRoot.setAttribute("aria-busy", "true");
            };

            if (hasHomeLoader) {
                pageRoot.classList.add("page-loading");
                pageRoot.setAttribute("aria-busy", "true");
            }

            document.addEventListener("click", (event) => {
                if (!(event.target instanceof Element)) {
                    return;
                }

                const link = event.target.closest("a[href]");
                if (
                    !link ||
                    event.defaultPrevented ||
                    event.button !== 0 ||
                    event.metaKey ||
                    event.ctrlKey ||
                    event.shiftKey ||
                    event.altKey ||
                    link.hasAttribute("download") ||
                    (link.target && link.target.toLowerCase() !== "_self")
                ) {
                    return;
                }

                const destination = new URL(link.href, window.location.href);
                if (
                    destination.origin !== window.location.origin ||
                    (destination.pathname === window.location.pathname &&
                        destination.search === window.location.search)
                ) {
                    return;
                }

                startPageLoading();
                window.setTimeout(() => {
                    if (event.defaultPrevented) {
                        stopPageLoading();
                    }
                }, 0);
            });

            document.addEventListener("submit", (event) => {
                const form = event.target;
                if (
                    event.defaultPrevented ||
                    !(form instanceof HTMLFormElement) ||
                    form.method.toLowerCase() === "dialog" ||
                    (form.target && form.target.toLowerCase() !== "_self")
                ) {
                    return;
                }

                const submitter = event.submitter;
                const action = submitter instanceof HTMLButtonElement || submitter instanceof HTMLInputElement
                    ? submitter.formAction || form.action
                    : form.action;
                const destination = new URL(action || window.location.href, window.location.href);
                if (destination.origin !== window.location.origin) {
                    return;
                }

                startPageLoading();
                window.setTimeout(() => {
                    if (event.defaultPrevented) {
                        stopPageLoading();
                    }
                }, 0);
            });

            window.addEventListener("load", () => {
                const remainingDuration = Math.max(
                    0,
                    minimumVisibleDuration - (performance.now() - loadingStartedAt)
                );

                initialLoadingTimeout = window.setTimeout(() => {
                    initialLoadingTimeout = null;
                    pageRoot.classList.remove("page-loading");
                    if (!navigationStarted) {
                        pageRoot.classList.add("page-enter");
                        window.setTimeout(() => pageRoot.classList.remove("page-enter"), 1600);
                        pageRoot.removeAttribute("aria-busy");
                    }
                }, remainingDuration);
            }, { once: true });

            window.addEventListener("pageshow", (event) => {
                if (event.persisted) {
                    stopPageLoading();
                }
            });
        })();
    </script>
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
