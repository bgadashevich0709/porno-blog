<?php

namespace App\Common\Controller;

use App\Common\Response\ResponseStrategyFactory;
use App\Common\Response\Startegy\ResponseStrategyInterface;

abstract class AbstractController
{
    private ResponseStrategyInterface $responseStrategy;

    public function __construct()
    {
        $this->responseStrategy = ResponseStrategyFactory::createFromCurrentRequest();

    }

    protected function render(string $target, array $data = []): void
    {
        $this->responseStrategy->render($target, $data);
    }
}
