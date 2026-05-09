<?php

namespace App\Support;

class TaxIdValidator
{
    public static function normalize(?string $value): string
    {
        return preg_replace('/\D+/', '', (string) $value) ?? '';
    }

    public static function validateCpf(string $value): bool
    {
        $cpf = self::normalize($value);

        if (strlen($cpf) !== 11) {
            return false;
        }

        if (preg_match('/^(\d)\1{10}$/', $cpf)) {
            return false;
        }

        for ($digit = 9; $digit < 11; $digit++) {
            $sum = 0;
            for ($i = 0; $i < $digit; $i++) {
                $sum += (int) $cpf[$i] * (($digit + 1) - $i);
            }
            $expected = (10 * $sum) % 11;
            if ($expected === 10) {
                $expected = 0;
            }
            if ($expected !== (int) $cpf[$digit]) {
                return false;
            }
        }

        return true;
    }

    public static function validateCnpj(string $value): bool
    {
        $cnpj = self::normalize($value);

        if (strlen($cnpj) !== 14) {
            return false;
        }

        if (preg_match('/^(\d)\1{13}$/', $cnpj)) {
            return false;
        }

        $weightsFirst = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $weightsSecond = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];

        $sum = 0;
        foreach ($weightsFirst as $i => $weight) {
            $sum += (int) $cnpj[$i] * $weight;
        }
        $remainder = $sum % 11;
        $first = $remainder < 2 ? 0 : 11 - $remainder;
        if ($first !== (int) $cnpj[12]) {
            return false;
        }

        $sum = 0;
        foreach ($weightsSecond as $i => $weight) {
            $sum += (int) $cnpj[$i] * $weight;
        }
        $remainder = $sum % 11;
        $second = $remainder < 2 ? 0 : 11 - $remainder;

        return $second === (int) $cnpj[13];
    }
}
