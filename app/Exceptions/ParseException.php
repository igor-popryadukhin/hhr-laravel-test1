<?php

namespace App\Exceptions;

use Exception;

/**
 * Любая ошибка парсинга: Яндекс не ответил, изменилась вёрстка,
 * капча, пустая страница, битый JSON...
 *
 * Ловится в OrganizationService::runParse — организации выставляется
 * status=failed с текстом ошибки, пользователю показывается в UI.
 */
class ParseException extends Exception
{
    public function __construct(string $message = '', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
