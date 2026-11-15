<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Error HTTP controlat (403, 404...). El router el converteix en una pàgina d'error.
 */
final class HttpException extends \RuntimeException
{
    /** @var int */
    private $status;

    public function __construct(int $status, string $message = '')
    {
        parent::__construct($message, $status);
        $this->status = $status;
    }

    public function status(): int
    {
        return $this->status;
    }
}
