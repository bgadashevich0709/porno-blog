<?php

namespace App\Common\Exception;

use App\Common\Response\ResponseStrategyFactory;
use App\Common\Response\Startegy\JsonStrategy;
use App\Common\Validator\Exception\ValidationException;
use App\Exceptions\AccessDeniedException;
use App\Exceptions\InvalidArgumentException; // Добавили импорт
use App\Exceptions\ResourceNotFoundException;
use Throwable;

class GlobalExceptionHandler
{
    public function register(): void
    {
        set_exception_handler([$this, 'handleException']);
    }

    /**
     * Перехват исключений
     */
    public function handleException(Throwable $exception): void
    {
        if (ob_get_length()) {
            ob_end_clean();
        }

        $strategy = ResponseStrategyFactory::createFromCurrentRequest();

        switch (true) {
            case $exception instanceof ResourceNotFoundException:
                $statusCode = 404;
                $template = 'errors/404.tpl';
                $data = [
                    'success' => false,
                    'message' => $exception->getMessage() ?: 'Страница не найдена',
                ];
                $this->logException($exception, 'WARNING', $statusCode);
                break;

            case $exception instanceof AccessDeniedException:
                $statusCode = 403;
                $template = 'errors/403.tpl';
                $data = [
                    'success' => false,
                    'message' => $exception->getMessage() ?: 'Доступ запрещен',
                ];
                $this->logException($exception, 'WARNING', $statusCode);
                break;

            case $exception instanceof InvalidArgumentException:
                $statusCode = $exception->getStatusCode();
                $template = 'errors/400.tpl';
                $data = [
                    'success' => false,
                    'message' => $exception->getMessage() ?: 'Некорректный запрос',
                ];
                $this->logException($exception, 'WARNING', $statusCode);
                break;

            case $exception instanceof ValidationException:
                $statusCode = 400;
                $template = 'errors/400.tpl';
                $data = [
                    'success' => false,
                    'message' => $exception->getMessage(),
                    'errors'  => $exception->getErrors(),
                ];
                $this->logException($exception, 'INFO', $statusCode);
                break;

            default:
                $statusCode = 500;
                $template = 'errors/500.tpl';
                $data = [
                    'success' => false,
                    'message' => 'Произошла внутренняя ошибка сервера.',
                ];

                if ($strategy instanceof JsonStrategy) {
                    $data['error_details'] = [
                        'message' => $exception->getMessage(),
                        'file' => $exception->getFile(),
                        'line' => $exception->getLine(),
                    ];
                } else {
                    $data['exception'] = $exception;
                }

                $this->logException($exception, 'ERROR', $statusCode, true);
                break;
        }

        http_response_code($statusCode);

        $strategy->render($template, $data);

        exit;
    }

    private function logException(Throwable $exception, string $level, int $statusCode, bool $includeTrace = false): void
    {
        $message = sprintf(
            "[%s] HTTP %d | %s: %s in %s on line %d",
            $level,
            $statusCode,
            get_class($exception),
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine()
        );

        if ($includeTrace) {
            $message .= "\nStack trace:\n" . $exception->getTraceAsString();
        }

        error_log($message);
    }
}
