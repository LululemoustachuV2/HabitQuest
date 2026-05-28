<?php

namespace App\Service\Random;

final class RandomPicker implements RandomPickerInterface
{
    public function pickInt(int $min, int $max): int
    {
        if ($max < $min) {
            throw new \InvalidArgumentException('max doit être >= min.');
        }

        return random_int($min, $max);
    }

    public function pickIndex(int $count): ?int
    {
        if ($count <= 0) {
            return null;
        }

        return $this->pickInt(0, $count - 1);
    }
}

