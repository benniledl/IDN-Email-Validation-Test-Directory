<?php

declare(strict_types=1);

final class View
{
    /**
     * @param array<string, mixed> $data
     */
    public static function render(string $template, array $data = []): void
    {
        extract($data, EXTR_SKIP);

        ob_start();
        require __DIR__ . '/../../views/' . $template . '.php';
        $content = (string)ob_get_clean();

        require __DIR__ . '/../../views/layout.php';
    }
}
