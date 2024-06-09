<?php

namespace App\Form\Admin;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class EmailAttachmentType extends AbstractType
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('attachment', FileType::class, [
                'label' => false,
                'attr' => [
                    'class' => 'form-control',
                ],
                'mapped' => false,
            ])
            ->add('mailId', HiddenType::class, [
                'mapped' => false,
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Добавить',
                'attr' => [
                    'class' => 'btn btn-block btn-outline-success',
                    'data-url' => $this->urlGenerator->generate('admin_email_add_file')
                ],
            ])
            ->setAction($this->urlGenerator->generate('admin_email_add_file'));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'attr' => [
                'id' => 'form-add-file',
            ],
        ]);
    }
}
