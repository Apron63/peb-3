<?php

namespace App\Form\Admin;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Trsteel\CkeditorBundle\Form\Type\CkeditorType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class DashboardType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('emailAttachmentStatisticText', CkeditorType::class, [
                'label' => 'Комментарий к вложению для статистики',
                'mapped' => false,
            ])
            ->add('emailAttachmentResultText', CkeditorType::class, [
                'label' => 'Комментарий к вложению для доступов',
                'mapped' => false,
            ])
            ->add('actionSubmit', SubmitType::class, [
                'label' => 'Сохранить',
                'attr' => [
                    'class' => 'btn btn-outline-dark',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
        ]);
    }
}
