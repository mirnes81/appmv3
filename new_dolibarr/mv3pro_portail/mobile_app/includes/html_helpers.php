<?php
/**
 * HTML Helpers
 * Fonctions utilitaires pour générer du HTML commun
 */

function renderHtmlHead($title, $additionalCss = [])
{
    $html = '<!DOCTYPE html>' . "\n";
    $html .= '<html lang="fr">' . "\n";
    $html .= '<head>' . "\n";
    $html .= '    <meta charset="UTF-8">' . "\n";
    $html .= '    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">' . "\n";
    $html .= '    <meta name="theme-color" content="#0891b2">' . "\n";
    $html .= '    <title>' . dol_escape_htmltag($title) . '</title>' . "\n";
    $html .= '    <link rel="stylesheet" href="../css/mobile_app.css">' . "\n";

    foreach ($additionalCss as $css) {
        $html .= '    <link rel="stylesheet" href="' . $css . '">' . "\n";
    }

    $html .= '</head>' . "\n";
    $html .= '<body>' . "\n";

    return $html;
}

function renderAppHeader($title, $subtitle = '', $backUrl = null, $backIcon = '←')
{
    $html = '<div class="app-header">' . "\n";

    if ($backUrl) {
        $html .= '    <a href="' . $backUrl . '" class="app-header-back">' . $backIcon . '</a>' . "\n";
    }

    $html .= '    <div>' . "\n";
    $html .= '        <div class="app-header-title">' . dol_escape_htmltag($title) . '</div>' . "\n";

    if ($subtitle) {
        $html .= '        <div class="app-header-subtitle">' . dol_escape_htmltag($subtitle) . '</div>' . "\n";
    }

    $html .= '    </div>' . "\n";
    $html .= '</div>' . "\n";

    return $html;
}

function renderAlertCard($message, $type = 'error')
{
    $colors = [
        'error' => ['bg' => '#fef2f2', 'border' => '#ef4444', 'text' => '#dc2626', 'icon' => '⚠️'],
        'success' => ['bg' => '#f0fdf4', 'border' => '#22c55e', 'text' => '#16a34a', 'icon' => '✅'],
        'info' => ['bg' => '#eff6ff', 'border' => '#3b82f6', 'text' => '#2563eb', 'icon' => 'ℹ️'],
        'warning' => ['bg' => '#fffbeb', 'border' => '#f59e0b', 'text' => '#d97706', 'icon' => '⚡']
    ];

    $color = $colors[$type] ?? $colors['info'];

    $html = '<div class="card" style="background: ' . $color['bg'] . '; border-left: 4px solid ' . $color['border'] . '; padding: 12px;">' . "\n";
    $html .= '    <div style="color: ' . $color['text'] . '; font-size: 14px;">' . "\n";
    $html .= '        ' . $color['icon'] . ' ' . dol_escape_htmltag($message) . "\n";
    $html .= '    </div>' . "\n";
    $html .= '</div>' . "\n";

    return $html;
}

function renderEmptyState($icon, $text, $actionButton = null)
{
    $html = '<div class="empty-state">' . "\n";
    $html .= '    <div class="empty-state-icon">' . $icon . '</div>' . "\n";
    $html .= '    <div class="empty-state-text">' . dol_escape_htmltag($text) . '</div>' . "\n";

    if ($actionButton) {
        $html .= '    <a href="' . $actionButton['url'] . '" class="btn btn-primary">' . dol_escape_htmltag($actionButton['text']) . '</a>' . "\n";
    }

    $html .= '</div>' . "\n";

    return $html;
}

function renderFAB($icon, $onClick)
{
    return '<button class="fab" onclick="' . $onClick . '">' . $icon . '</button>' . "\n";
}

function renderHtmlFooter($includeBottomNav = true, $additionalScripts = [])
{
    $html = '';

    if ($includeBottomNav) {
        $html .= '<?php include "../includes/bottom_nav.php"; ?>' . "\n";
    }

    foreach ($additionalScripts as $script) {
        $html .= '<script src="' . $script . '"></script>' . "\n";
    }

    $html .= '</body>' . "\n";
    $html .= '</html>' . "\n";

    return $html;
}

function startAppContainer()
{
    return '<div class="app-container">' . "\n";
}

function endAppContainer()
{
    return '</div>' . "\n";
}
