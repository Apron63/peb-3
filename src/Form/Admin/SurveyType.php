<?php

declare(strict_types=1);

namespace App\Form\Admin;

use App\Entity\Profile;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SurveyType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('startPeriod', DateTimeType::class, [
                'required' => false,
                'widget' => 'single_text',
                'format' => 'dd.MM.yyyy',
                'html5' => false,
                'label' => 'Начало',
                'mapped' => false,
                'attr' => [
                    'class' => 'form-control col-6',
                    'placeholder' => 'Начало периода',
                    'onfocus' => 'this.placeholder = ""',
                    'onblur' => 'this.placeholder = "Начало периода"',
                    'autocomplete' => 'off',
                    'readonly' => true,
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
                    'placeholder' => 'Окончание периода',
                    'onfocus' => 'this.placeholder = ""',
                    'onblur' => 'this.placeholder = "Окончание периода"',
                    'autocomplete' => 'off',
                    'readonly' => true,
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
                'multiple' => true,
                'placeholder' => 'Все профили',
                'attr' => [
                    'class' => 'form-select',
                    'placeholder' => 'Профиль',
                    'onfocus' => 'this.placeholder = ""',
                    'onblur' => 'this.placeholder = "Профиль"',
                    'size' => 10,
                ],
                'label_attr' => [
                    'class' => 'col-sm-2 col-form-label'
                ],
            ])
            ->add('reportType', ChoiceType::class, [
                'choices'  => [
                    'PDF' => 'pdf',
                    'XLSX' => 'xlsx',
                    'DOCX' => 'docx',
                ],
                'required' => true,
                'label' => 'Формат',
                'mapped' => false,
                'placeholder' => 'Формат отчета',
                'attr' => [
                    'class' => 'form-select',
                    'placeholder' => 'Формат отчета',
                    'onfocus' => 'this.placeholder = ""',
                    'onblur' => 'this.placeholder = "Формат отчета"',
                    'size' => 3,
                ],
                'label_attr' => [
                    'class' => 'col-sm-2 col-form-label'
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Сформировать отчет',
                'attr' => [
                    'class' => 'btn btn-outline-dark',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
