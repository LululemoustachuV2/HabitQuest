<?php

namespace App\Service\Random;

interface RandomPickerInterface
{
    public function pickInt(int $min, int $max): int;

    public function pickIndex(int $count): ?int;
}

