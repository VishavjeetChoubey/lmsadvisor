<?php
declare(strict_types=1);

namespace App\Core;

class View
{
    /**
     * Render a view file with optional data.
     *
     * @param string $view  Dot-notation path relative to app/Views/
     *                      e.g. 'admin.dashboard.index'
     * @param array  $data  Variables to extract into the view scope
     */
    public static function render(string $view, array $data = []): string
    {
        $file = VIEW_PATH . '/' . str_replace('.', '/', $view) . '.php';

        if (!file_exists($file)) {
            throw new \RuntimeException("View not found: $file");
        }

        // Extract data into local scope
        extract($data, EXTR_SKIP);

        ob_start();
        require $file;
        return (string)ob_get_clean();
    }

    /**
     * Render a view wrapped in a layout.
     *
     * @param string $layout  Layout name under app/Views/layouts/
     * @param string $view    View file (dot notation)
     * @param array  $data    Data for both layout and view
     */
    public static function renderWithLayout(string $layout, string $view, array $data = []): string
    {
        // Render the inner view first
        $content = self::render($view, $data);

        // Pass $content into the layout
        $data['content'] = $content;

        return self::render('layouts.' . $layout, $data);
    }

    /**
     * Escape output — always use this in views.
     */
    public static function e(mixed $value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * Generate an asset URL from /public/assets/.
     * Works whether index.php is at root or inside public/.
     */
    public static function asset(string $path): string
    {
        return APP_URL . '/public/assets/' . ltrim($path, '/');
    }

    /**
     * Generate an app URL.
     */
    public static function url(string $path = ''): string
    {
        return APP_URL . '/' . ltrim($path, '/');
    }
}
