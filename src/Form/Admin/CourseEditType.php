<?php

namespace App\Form\Admin;

use App\Entity\Course;
use App\Entity\Profile;
use App\Repository\ProfileRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

class CourseEditType extends AbstractType
{
    public function __construct(
        private readonly ProfileRepository $profileRepository
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('shortName', TextType::class, [
                'required' => false,
                'attr' => [
                    'label' => false,
                    'class' => 'form-control',
                    'placeholder' => 'Наименование (для админки)',
                    'onfocus' => 'this.placeholder = ""',
                    'onblur' => 'this.placeholder = "Наименование (для админки)"',
                ],
            ])
            ->add('name', TextType::class, [
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Обозначение (для слушателя)',
                    'onfocus' => 'this.placeholder = ""',
                    'onblur' => 'this.placeholder = "Обозначение (для слушателя)"',
                ],
            ])
            ->add('profile', EntityType::class, [
                'class' => Profile::class,
                'choices' => $this->profileRepository->getAllProfilesAsCollection(),
                'attr' => [
                    'label' => false,
                    'class' => 'form-select',
                    'placeholder' => 'Обозначение',
                    'onfocus' => 'this.placeholder = ""',
                    'onblur' => 'this.placeholder = "Обозначение"',
                ],
            ])
            ->add('image', FileType::class, [
                'label' => 'Изображение',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '300k',
                        'mimeTypes' => [
                            'image/jpg',
                            'image/jpeg',
                            'image/png',
                        ],
                        'mimeTypesMessage' => 'Допускаются только форматы JPG и PNG',
                    ])
                ],
            ])
           ->add('forDemo', CheckboxType::class, [
               'label' => 'Демо-версия',
               'required' => false,
               'attr' => [
                   'class' => 'form-check-input',
               ],
               'label_attr' => [
                   'class' => 'form-check-label',
               ],
           ]);

        if ($options['data']->getId() === null) {
            $builder->add('type', ChoiceType::class, [
                'label' => 'Тип курсов',
                'choices'  => [
                    'Вопрос-ответ' => Course::CLASSC,
                    'Интерактивные' => Course::INTERACTIVE,
                ],
                'attr' => [
                    'label' => false,
                    'class' => 'form-select',
                    'placeholder' => 'Тип курса',
                    'onfocus' => 'this.placeholder = ""',
                    'onblur' => 'this.placeholder = "Тип курса"',
                    'disabled' => $options['data']->getId() !== null,
                ],
            ]);
        }

        $builder->add('submit', SubmitType::class, [
            'label' => 'Сохранить',
            'attr' => [
                'class' => 'btn btn-outline-dark',
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Course::class,
        ]);
    }
}
