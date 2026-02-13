<?php

namespace MrBohem\Larasync\Support;

class SyncResult
{
    public function __construct(
        public readonly bool $success,
        public readonly int $rowCount,
        public readonly string $message,
    ) {
    }
}
