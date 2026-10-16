<?php

declare(strict_types=1);

namespace App\Core;

final class Kernel
{
    public static function handle(string $method, string $path): Response
    {
        $router = new Router();
        require ROOT_PATH . '/src/routes.php';

        http_response_code(200);
        ob_start();

        try {
            $router->dispatch($method, $path);

            return new Response(http_response_code() ?: 200, (string) ob_get_clean());
        } catch (Redirect $redirect) {
            ob_end_clean();

            return new Response($redirect->status, '', $redirect->location);
        } catch (HttpError $error) {
            ob_end_clean();

            return new Response($error->status, render_error($error->status));
        } catch (\Throwable $throwable) {
            ob_end_clean();

            throw $throwable;
        }
    }
}
