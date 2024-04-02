<?php

namespace App\Form\Admin;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\File;

class Load1CType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('filename', FileType::class, [
                'label' => 'Список слушателей',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                       'maxSize' => '100m',
                       'mimeTypes' => [
                           'text/plain',
                       ],
                       'mimeTypesMessage' => 'Выбранный файл не является TXT файлом',
                    ])
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Загрузить',
                'attr' => [
                    'class' => 'btn btn-outline-dark',
                ],
            ]);
    }
}
