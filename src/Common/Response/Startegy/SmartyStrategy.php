<?php

namespace App\Common\Response\Startegy;

use Smarty\Smarty;

class SmartyStrategy implements ResponseStrategyInterface
{
    private Smarty $smarty;

    public function __construct()
    {
        $this->smarty = new Smarty();
        $rootDir = dirname(__DIR__, 4);

        $this->smarty->setTemplateDir($rootDir . '/templates');
        $this->smarty->setCompileDir($rootDir . '/var/smarty/templates_c');
        $this->smarty->setCacheDir($rootDir . '/var/smarty/cache');
    }

    public function render(string $target, array $data): void
    {
        header('Content-Type: text/html; charset=utf-8');

        foreach ($data as $key => $value) {
            $this->smarty->assign($key, $value);
        }

        $this->smarty->display($target);
    }
}
