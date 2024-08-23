<?php

namespace App\Form\Admin;

use App\Entity\Module;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class ModuleEditType extends AbstractType
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
                'constraints' => [
                    new Length([
                        'max' => 255,
                    ]),
                    new NotBlank(),
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
            'data_class' => Module::class,
        ]);
    }
}
