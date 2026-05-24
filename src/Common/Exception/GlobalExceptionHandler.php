<?php

namespace App\Common\Exception;

use App\Common\Response\ResponseStrategyFactory;
use App\Common\Response\Startegy\JsonStrategy;
use App\Common\Validator\Exception\ValidationException;
use App\Exceptions\AccessDeniedException;
use App\Exceptions\InvalidArgumentException;
use App\Exceptions\ResourceNotFoundException;
use Psr\Log\LoggerInterface;
use Throwable;

class GlobalExceptionHandler
{
    // Моментально объявляем и инициализируем свойство прямо здесь
    public function __construct(
        private LoggerInterface $logger
    ) {}

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
                $this->logger->warning($exception->getMessage() ?: 'Страница не найдена', [
                    'status_code' => $statusCode,
                    'exception'   => get_class($exception),
                    'file'        => $exception->getFile(),
                    'line'        => $exception->getLine(),
                ]);
                break;

            case $exception instanceof AccessDeniedException:
                $statusCode = 403;
                $template = 'errors/403.tpl';
                $data = [
                    'success' => false,
                    'message' => $exception->getMessage() ?: 'Доступ запрещен',
                ];
                $this->logger->warning($exception->getMessage() ?: 'Доступ запрещен', [
                    'status_code' => $statusCode,
                    'exception'   => get_class($exception),
                    'file'        => $exception->getFile(),
                    'line'        => $exception->getLine(),
                ]);
                break;

            case $exception instanceof InvalidArgumentException:
                $statusCode = $exception->getStatusCode();
                $template = 'errors/400.tpl';
                $data = [
                    'success' => false,
                    'message' => $exception->getMessage() ?: 'Некорректный запрос',
                ];
                $this->logger->warning($exception->getMessage() ?: 'Некорректный запрос', [
                    'status_code' => $statusCode,
                    'exception'   => get_class($exception),
                    'file'        => $exception->getFile(),
                    'line'        => $exception->getLine(),
                ]);
                break;

            case $exception instanceof ValidationException:
                $statusCode = 400;
                $template = 'errors/400.tpl';
                $data = [
                    'success' => false,
                    'message' => $exception->getMessage(),
                    'errors'  => $exception->getErrors(),
                ];
                $this->logger->info($exception->getMessage(), [
                    'status_code' => $statusCode,
                    'exception'   => get_class($exception),
                    'errors'      => $exception->getErrors(),
                    'file'        => $exception->getFile(),
                    'line'        => $exception->getLine(),
                ]);
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

                $this->logger->error($exception->getMessage(), [
                    'status_code' => $statusCode,
                    'exception'   => get_class($exception),
                    'file'        => $exception->getFile(),
                    'line'        => $exception->getLine(),
                    'trace'       => $exception->getTraceAsString(),
                ]);
                break;
        }

        http_response_code($statusCode);

        $strategy->render($template, $data);

        exit;
    }
}
