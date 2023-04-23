<?php

namespace App\Form\Admin;

use App\Entity\ModuleSection;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class ModuleSectionEditType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Наименование',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Наименование',
                    'onfocus' => 'this.placeholder = ""',
                    'onblur' => 'this.placeholder = "Наименование"',
                ],
                'label_attr' => [
                    'class' => 'col-sm-2 col-form-label'
                ],
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Тип материалов',
                'choices' => ModuleSection::PAGE_TYPES,
                'attr' => [
                    'class' => 'form-select',
                    'placeholder' => 'Тип раздела',
                    'onfocus' => 'this.placeholder = ""',
                    'onblur' => 'this.placeholder = "Тип раздела"',
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
            'data_class' => ModuleSection::class,
        ]);
    }
}
