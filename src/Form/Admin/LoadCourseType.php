<?php

namespace App\Form\Admin;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class LoadCourseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('filename', FileType::class, [
                'label' => 'ZIP архив',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new NotBlank(),
                    new File([
                        'maxSize' => '100m',
                        'mimeTypes' => [
                            'application/zip',
                        ],
                        'mimeTypesMessage' => 'Выбранный файл не является ZIP архивом',
                    ]),
                    new Callback([$this, 'validateFileNameMaxLength']),
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Загрузить',
                'attr' => [
                    'class' => 'btn btn-outline-dark',
                ],
            ]);
    }

    public function validateFileNameMaxLength(?UploadedFile $file, ExecutionContextInterface $context): void
    {
        if (null !== $file) {
            $originalFileName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $fileNameLength = mb_strlen($originalFileName);

            if ($fileNameLength > 150) {
                $context->addViolation('Длина имени файла: ' . $fileNameLength . ' символов, превышает допустимое значние 150 символов');
            }
        }
    }
}
