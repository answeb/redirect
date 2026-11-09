<?php

declare(strict_types=1);

namespace PrestaShop\Module\Redirect\Form;

use PrestaShop\PrestaShop\Core\Form\IdentifiableObject\DataProvider\FormDataProviderInterface;
use PrestaShop\Module\Redirect\Enum\HttpRedirectCode;
use PrestaShop\Module\Redirect\Repository\RedirectRuleRepository;

class RedirectFormDataProvider implements FormDataProviderInterface
{
    public function __construct(
        private readonly RedirectRuleRepository $repository
    ) {
    }

    public function getData($id): array
    {
        $rule = $this->repository->find((int) $id);

        if (null === $rule) {
            throw new \RuntimeException(sprintf('Redirection #%d introuvable.', $id));
        }

        return [
            'source' => $rule['source'],
            'destination' => $rule['destination'],
            'is_regex' => (bool) $rule['is_regex'],
            'http_code' => (int) $rule['http_code'],
            'active' => (bool) $rule['active'],
        ];
    }

    public function getDefaultData(): array
    {
        return [
            'source' => '',
            'destination' => '',
            'is_regex' => false,
            'http_code' => HttpRedirectCode::MovedPermanently->value,
            'active' => true,
        ];
    }
}
