<?php

namespace App\Form\Admin;

use App\Entity\Action;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ActionIntervalType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('startAt', DateTimeType::class, [
                'widget' => 'single_text',
                'format' => 'dd.MM.yyyy',
                'html5' => false,
                'label' => 'Начало',
                'mapped' => false,
                'attr' => [
                    'class' => 'form-control col-6',
                    'placeholder' => 'Начало',
                    'onfocus' => 'this.placeholder = ""',
                    'onblur' => 'this.placeholder = "Начало"',
                    'autocomplete' => 'off',
                ],
                'label_attr' => [
                    'class' => 'col-sm-2 col-form-label'
                ],
            ])
            ->add('endAt', DateTimeType::class, [
                'widget' => 'single_text',
                'format' => 'dd.MM.yyyy',
                'html5' => false,
                'label' => 'Окончание',
                'mapped' => false,
                'attr' => [
                    'class' => 'form-control col-6',
                    'placeholder' => 'Окончание',
                    'onfocus' => 'this.placeholder = ""',
                    'onblur' => 'this.placeholder = "Окончание"',
                    'autocomplete' => 'off',
                ],
                'label_attr' => [
                    'class' => 'col-sm-2 col-form-label'
                ],
            ])
            ->add('actionSubmit', SubmitType::class, [
                'label' => 'Создать отчет',
                'attr' => [
                    'class' => 'btn btn-outline-dark',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Action::class,
        ]);
    }
}
