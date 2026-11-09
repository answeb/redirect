<?php

declare(strict_types=1);

namespace PrestaShop\Module\Redirect\Form;

use PrestaShop\Module\Redirect\Enum\HttpRedirectCode;
use PrestaShopBundle\Form\Admin\Type\SwitchType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class RedirectType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('source', TextType::class, [
                'label' => 'Source',
                'help' => 'Chemin exact (ex. /ancienne-page) ou expression régulière si "Regex" est activé, sans les délimiteurs.',
                'constraints' => [
                    new NotBlank(),
                    new Length(['max' => 2048]),
                ],
            ])
            ->add('destination', TextType::class, [
                'label' => 'Destination',
                'help' => 'URL cible. En mode regex, $1, $2… reprennent les groupes capturés dans la source.',
                'constraints' => [
                    new NotBlank(),
                    new Length(['max' => 2048]),
                ],
            ])
            ->add('is_regex', SwitchType::class, [
                'label' => 'Expression régulière',
                'required' => false,
            ])
            ->add('http_code', ChoiceType::class, [
                'label' => 'Code HTTP',
                'choices' => HttpRedirectCode::choices(),
            ])
            ->add('active', SwitchType::class, [
                'label' => 'Active',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'constraints' => [
                new Callback([$this, 'validateRegex']),
            ],
        ]);
    }

    public function validateRegex(?array $data, ExecutionContextInterface $context): void
    {
        if (empty($data['is_regex']) || empty($data['source'])) {
            return;
        }

        if (@preg_match('#' . $data['source'] . '#u', '') === false) {
            $context->buildViolation('Cette expression régulière est invalide.')
                ->atPath('source')
                ->addViolation();
        }
    }
}
