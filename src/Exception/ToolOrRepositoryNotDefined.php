<?php
namespace App\Exception;

class ToolOrRepositoryNotDefined extends \RuntimeException
{
    public function __construct(string $name, \Throwable $previous = null)
    {
        $message = sprintf('The Tool or Repository "%s" not defined.', $name);
        parent::__construct($message, 0, $previous);
    }
}
