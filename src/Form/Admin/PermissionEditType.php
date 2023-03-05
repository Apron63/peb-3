<?php

namespace App\Form\Admin;

use App\Entity\Course;
use App\Entity\Permission;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PermissionEditType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('createdAt', DateTimeType::class, [
                'widget' => 'single_text',
                'label' => 'Создано',
                'format' => 'dd.mm.yyyy',
                'html5' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Создано',
                    'onfocus' => 'this.placeholder = ""',
                    'onblur' => 'this.placeholder = "Создано"',
                ],
                'label_attr' => [
                    'class' => 'col-sm-2 col-form-label'
                ],
            ])
            ->add('duration', TextType::class, [
                'label' => 'Длительность',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Длительность',
                    'onfocus' => 'this.placeholder = ""',
                    'onblur' => 'this.placeholder = "Длительность"',
                ],
                'label_attr' => [
                    'class' => 'col-sm-2 col-form-label'
                ],
            ])
            ->add('orderNom', TextType::class, [
                'required' => false,
                'label' => 'Заказ',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Заказ',
                    'onfocus' => 'this.placeholder = ""',
                    'onblur' => 'this.placeholder = "Заказ"',
                ],
                'label_attr' => [
                    'class' => 'col-sm-2 col-form-label'
                ],
            ])
            ->add('course', EntityType::class, [
                'class' => Course::class,
                'label' => 'Курс',
                'attr' => [
                    'class' => 'form-select',
                    'placeholder' => 'Курс',
                    'onfocus' => 'this.placeholder = ""',
                    'onblur' => 'this.placeholder = "Курс"',
                ],
                'label_attr' => [
                    'class' => 'col-sm-2 col-form-label'
                ],
            ])
            ->add('activatedAt', DateTimeType::class, [
                'widget' => 'single_text',
                'required' => false,
                'label' => 'Активировано',
                'format' => 'dd.mm.yyyy',
                'html5' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Не указано',
                    'onfocus' => 'this.placeholder = ""',
                    'onblur' => 'this.placeholder = "Не указано"',
                ],
                'label_attr' => [
                    'class' => 'col-sm-2 col-form-label'
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Сохранить',
                'attr' => [
                    'class' => 'btn btn-outline-dark',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Permission::class,
        ]);
    }
}
