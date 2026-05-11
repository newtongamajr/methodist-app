<?php

namespace App\Enums;

/**
 * The 26 Brazilian states + the Federal District + a synthetic "Foreign" case
 * that addresses outside Brazil opt into. When `Foreign` is picked, the UI
 * surfaces a Country select; for any real state, the country is implicitly BR.
 */
enum BrazilianState: string
{
    case AC = 'AC';
    case AL = 'AL';
    case AP = 'AP';
    case AM = 'AM';
    case BA = 'BA';
    case CE = 'CE';
    case DF = 'DF';
    case ES = 'ES';
    case GO = 'GO';
    case MA = 'MA';
    case MT = 'MT';
    case MS = 'MS';
    case MG = 'MG';
    case PA = 'PA';
    case PB = 'PB';
    case PR = 'PR';
    case PE = 'PE';
    case PI = 'PI';
    case RJ = 'RJ';
    case RN = 'RN';
    case RS = 'RS';
    case RO = 'RO';
    case RR = 'RR';
    case SC = 'SC';
    case SP = 'SP';
    case SE = 'SE';
    case TO = 'TO';
    case Foreign = 'FN';

    public function label(): string
    {
        return match ($this) {
            self::AC => __('Acre'),
            self::AL => __('Alagoas'),
            self::AP => __('Amapá'),
            self::AM => __('Amazonas'),
            self::BA => __('Bahia'),
            self::CE => __('Ceará'),
            self::DF => __('Distrito Federal'),
            self::ES => __('Espírito Santo'),
            self::GO => __('Goiás'),
            self::MA => __('Maranhão'),
            self::MT => __('Mato Grosso'),
            self::MS => __('Mato Grosso do Sul'),
            self::MG => __('Minas Gerais'),
            self::PA => __('Pará'),
            self::PB => __('Paraíba'),
            self::PR => __('Paraná'),
            self::PE => __('Pernambuco'),
            self::PI => __('Piauí'),
            self::RJ => __('Rio de Janeiro'),
            self::RN => __('Rio Grande do Norte'),
            self::RS => __('Rio Grande do Sul'),
            self::RO => __('Rondônia'),
            self::RR => __('Roraima'),
            self::SC => __('Santa Catarina'),
            self::SP => __('São Paulo'),
            self::SE => __('Sergipe'),
            self::TO => __('Tocantins'),
            self::Foreign => __('Foreign'),
        };
    }

    public function isForeign(): bool
    {
        return $this === self::Foreign;
    }

    /** @return array<string, string> Map of UF acronym → translated state name. */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
