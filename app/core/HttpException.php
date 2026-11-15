<?php
declare(strict_types=1);

namespace App\Core;

final class HttpException extends \RuntimeException
{
    public function __construct(public readonly int $estado, string $mensaje = '')
    {
        parent::__construct($mensaje, $estado);
    }
}
