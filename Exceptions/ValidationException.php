<?php

namespace Modules\NobilikGroupedTags\Exceptions;

use Exception;

/**
 * Исключение, бросаемое при нарушении правил обязательных тегов.
 * Используется в Observer для блокировки сохранения заявки.
 */
class ValidationException extends Exception
{
    /**
     * Конструктор.
     *
     * @param string $message Сообщение об ошибке, которое будет показано пользователю.
     * @param int $code Код ошибки.
     * @param \Throwable|null $previous Предыдущее исключение.
     */
    public function __construct(string $message = "", int $code = 0, \Throwable $previous = null)
    {
        // Устанавливаем код HTTP ответа, если это необходимо.
        // Для FreeScout часто достаточно простого сообщения.
        parent::__construct($message, $code, $previous);
    }
}