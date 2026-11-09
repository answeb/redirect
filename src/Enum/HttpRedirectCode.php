<?php

declare(strict_types=1);

namespace PrestaShop\Module\Redirect\Enum;

enum HttpRedirectCode: int
{
    case MovedPermanently = 301;
    case Found = 302;
    case TemporaryRedirect = 307;
    case PermanentRedirect = 308;

    public function label(): string
    {
        return match ($this) {
            self::MovedPermanently => '301 - Redirection permanente',
            self::Found => '302 - Redirection temporaire',
            self::TemporaryRedirect => '307 - Redirection temporaire (méthode conservée)',
            self::PermanentRedirect => '308 - Redirection permanente (méthode conservée)',
        };
    }

    /**
     * @return array<string, int>
     */
    public static function choices(): array
    {
        $choices = [];
        foreach (self::cases() as $case) {
            $choices[$case->label()] = $case->value;
        }

        return $choices;
    }
}
