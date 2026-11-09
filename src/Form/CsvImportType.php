<?php

declare(strict_types=1);

namespace PrestaShop\Module\Redirect\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\File;

class CsvImportType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('file', FileType::class, [
            'label' => 'Fichier CSV (source;destination;code)',
            'constraints' => [
                new File([
                    'maxSize' => '2M',
                    'extensions' => ['csv'],
                    'extensionsMessage' => 'Merci de sélectionner un fichier CSV.',
                ]),
            ],
        ]);
    }
}
