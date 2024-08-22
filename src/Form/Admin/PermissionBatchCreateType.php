<?php

namespace App\Form\Admin;

use App\Entity\Course;
use App\Entity\Permission;
use App\Entity\Profile;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\LessThanOrEqual;

class PermissionBatchCreateType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('duration', TextType::class, [
                'mapped' => false,
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
                'constraints' => [
                    new LessThanOrEqual(Permission::MAX_DURATION),
                ],
            ])
            ->add('orderNom', TextType::class, [
                'mapped' => false,
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
                'mapped' => false,
                'class' => Course::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('c')
                        ->where('c.forDemo = 0 AND c.hidden = 0')
                        ->orderBy('c.shortName', 'ASC');
                },
                'label' => 'Курс',
                'multiple' => true,
                'attr' => [
                    'class' => 'form-select',
                    'placeholder' => 'Курс',
                    'onfocus' => 'this.placeholder = ""',
                    'onblur' => 'this.placeholder = "Курс"',
                    'multiple' => 'multiple',
                    'size' => 10,
                ],
                'label_attr' => [
                    'class' => 'col-sm-2 col-form-label'
                ],
                'choice_attr' => function (Course $course) {
                    return [
                        'data-profile' => $course->getProfile()?->getId(),
                    ];
                },
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
                    'v-on:keyup' => 'applyFilter',
                    'id' => 'user_search_lifeSearch',
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
            ->add('submit', SubmitType::class, [
                'label' => 'Выполнить',
                'attr' => [
                    'class' => 'btn btn-outline-dark',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
        ]);
    }
}
