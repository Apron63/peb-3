<?php

namespace App\Form\Admin;

use App\Entity\Course;
use App\Entity\Profile;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class UserSearchType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('login', TextType::class, [
                'required' => false,
                'label' => 'Логин',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Логин',
                    'onfocus' => 'this.placeholder = ""',
                    'onblur' => 'this.placeholder = "Логин"',
                ],
                'label_attr' => [
                    'class' => 'col-sm-2 col-form-label'
                ],
            ])
            ->add('name', TextType::class, [
                'required' => false,
                'label' => 'ФИО',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'ФИО',
                    'onfocus' => 'this.placeholder = ""',
                    'onblur' => 'this.placeholder = "ФИО"',
                ],
                'label_attr' => [
                    'class' => 'col-sm-2 col-form-label'
                ],
            ])
            ->add('organization', TextType::class, [
                'required' => false,
                'label' => 'Организация',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Организация',
                    'onfocus' => 'this.placeholder = ""',
                    'onblur' => 'this.placeholder = "Организация"',
                ],
                'label_attr' => [
                    'class' => 'col-sm-2 col-form-label'
                ],
            ])
            ->add('position', TextType::class, [
                'required' => false,
                'label' => 'Должность',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Должность',
                    'onfocus' => 'this.placeholder = ""',
                    'onblur' => 'this.placeholder = "Должность"',
                ],
                'label_attr' => [
                    'class' => 'col-sm-2 col-form-label'
                ],
            ])
            ->add('orderNumber', TextType::class, [
                'required' => false,
                'label' => 'Номер заказа',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Номер заказа',
                    'onfocus' => 'this.placeholder = ""',
                    'onblur' => 'this.placeholder = "Номер заказа"',
                ],
                'label_attr' => [
                    'class' => 'col-sm-2 col-form-label'
                ],
            ])
            ->add('startPeriod', DateTimeType::class, [
                'required' => false,
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
            ->add('endPeriod', DateTimeType::class, [
                'required' => false,
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
            ->add('course', EntityType::class, [
                'class' => Course::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('c')
                        ->orderBy('c.shortName', 'ASC');
                },
                'multiple' => true,
                'required' => false,
                'empty_data' => '',
                'choice_label' => 'shortName',
                'label' => 'Курсы',
                'attr' => [
                    'class' => 'form-select',
                ],
                'choice_attr' => function (Course $course) {
                    return [
                        'data-profile' => $course->getProfile()
                            ? $course->getProfile()->getId()
                            : null,
                    ];
                },
                'label_attr' => [
                    'class' => 'col-sm-2 col-form-label'
                ],
            ])
            ->add('lifeSearch', TextType::class, [
                'required' => false,
                'label' => 'Быстрый поиск',
                'mapped' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Быстрый поиск',
                    'onfocus' => 'this.placeholder = ""',
                    'onblur' => 'this.placeholder = "Быстрый поиск"',
                    'v-on:keyup' => 'applyFilter'
                ],
                'label_attr' => [
                    'class' => 'col-sm-2 col-form-label'
                ],
            ])
            ->add('profile', EntityType::class, [
                'class' => Profile::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('p')
                        ->orderBy('p.name', 'ASC');
                },
                'required' => false,
                'label' => 'Профиль',
                'mapped' => false,
                'placeholder' => 'Все профили',
                'attr' => [
                    'class' => 'form-select',
                    'placeholder' => 'Профиль',
                    'onfocus' => 'this.placeholder = ""',
                    'onblur' => 'this.placeholder = "Профиль"',
                ],
                'label_attr' => [
                    'class' => 'col-sm-2 col-form-label'
                ],
            ])
            ->add('search', SubmitType::class, [
                'label' => 'Поиск',
                'attr' => [
                    'class' => 'btn btn-outline-dark',
                ],
            ])
            ->setMethod('GET');
    }
}
