<?php

declare(strict_types=1);

namespace PrestaShop\Module\Redirect\Service;

final class RedirectMatcher
{
    /**
     * @param array<int, array{source: string, destination: string, is_regex: int|bool, http_code: int|string}> $rules
     */
    public function __construct(
        private readonly array $rules
    ) {
    }

    /**
     * @return array{destination: string, http_code: int}|null
     */
    public function match(string $requestPath): ?array
    {
        foreach ($this->rules as $rule) {
            if ((bool) $rule['is_regex']) {
                $pattern = '#' . $rule['source'] . '#u';

                if (@preg_match($pattern, $requestPath) !== 1) {
                    continue;
                }

                $destination = @preg_replace($pattern, $rule['destination'], $requestPath);

                if (!is_string($destination)) {
                    continue;
                }

                return [
                    'destination' => $destination,
                    'http_code' => (int) $rule['http_code'],
                ];
            }

            if ($this->normalize($rule['source']) === $this->normalize($requestPath)) {
                return [
                    'destination' => $rule['destination'],
                    'http_code' => (int) $rule['http_code'],
                ];
            }
        }

        return null;
    }

    private function normalize(string $path): string
    {
        return rtrim($path, '/') ?: '/';
    }
}
