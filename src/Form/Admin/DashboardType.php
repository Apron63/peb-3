<?php

declare (strict_types=1);

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
            ->add('userHasNewPermission', CkeditorType::class, [
                'label' => 'Информирование слушателя о назначении доступа по Email',
                'mapped' => false,
            ])
            ->add('userHasActivatedPermission', CkeditorType::class, [
                'label' => 'Информирование слушателя после активации курса по Email',
                'mapped' => false,
            ])
            ->add('permissionWillEndSoon', CkeditorType::class, [
                'label' => 'Информирование слушателя об окончании действия курса по Email',
                'mapped' => false,
            ])
            ->add('userHasNewPermissionWhatsapp', CkeditorType::class, [
                'label' => 'Информирование слушателя о назначении доступа по WhatsApp',
                'mapped' => false,
            ])
            ->add('userHasActivatedPermissionWhatsapp', CkeditorType::class, [
                'label' => 'Информирование слушателя после активации курса по WhatsApp',
                'mapped' => false,
            ])
            ->add('permissionWillEndSoonWhatsapp', CkeditorType::class, [
                'label' => 'Информирование слушателя об окончании действия курса по WhatsApp',
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
