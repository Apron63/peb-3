<?php

namespace App\Form\Admin;

use App\Entity\CourseTheme;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CourseThemeEditType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'required' => false,
                'attr' => [
                    'label' => false,
                    'class' => 'form-control',
                    'placeholder' => 'Название',
                    'onfocus' => 'this.placeholder = ""',
                    'onblur' => 'this.placeholder = "Название"',
                ],
            ])
            ->add('description', TextareaType::class, [
                'required' => false,
                'attr' => [
                    'label' => false,
                    'class' => 'form-control',
                    'placeholder' => 'Описание',
                    'onfocus' => 'this.placeholder = ""',
                    'onblur' => 'this.placeholder = "Описание"',
                    'rows' => 6,
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
            'data_class' => CourseTheme::class,
        ]);
    }
}
