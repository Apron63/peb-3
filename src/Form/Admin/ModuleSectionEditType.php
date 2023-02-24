<?php

namespace App\Form\Admin;

use App\Entity\ModuleInfo;
use App\Entity\ModuleSection;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

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
            ->add('url', TextType::class, [
                'required' => false,
                'label' => 'Ссылка',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ссылка',
                    'onfocus' => 'this.placeholder = ""',
                    'onblur' => 'this.placeholder = "Ссылка"',
                ],
                'label_attr' => [
                    'class' => 'col-sm-2 col-form-label'
                ],
            ])
            ->add('filename', FileType::class, [
                'label' => 'ZIP архив',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '100m',
                        'mimeTypes' => [
                            'application/zip',
                        ],
                        'mimeTypesMessage' => 'Выбранный файл не является ZIP архивом',
                    ])
                ],
            ])
            ->add('urlType', ChoiceType::class, [
                'label' => 'Тип материалов',
                'choices' => ModuleInfo::URL_TYPES,
                'attr' => [
                    'class' => 'form-select',
                    'placeholder' => 'Тип материалов',
                    'onfocus' => 'this.placeholder = ""',
                    'onblur' => 'this.placeholder = "Ссылка"',
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
