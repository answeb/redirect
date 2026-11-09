<?php

declare(strict_types=1);

namespace PrestaShop\Module\Redirect\Form;

use PrestaShop\PrestaShop\Core\Form\IdentifiableObject\DataHandler\FormDataHandlerInterface;
use PrestaShop\Module\Redirect\Repository\RedirectRuleRepository;

class RedirectFormDataHandler implements FormDataHandlerInterface
{
    public function __construct(
        private readonly RedirectRuleRepository $repository
    ) {
    }

    public function create(array $data)
    {
        return $this->repository->add($data);
    }

    public function update($id, array $data)
    {
        $this->repository->update((int) $id, $data);

        return $id;
    }
}
