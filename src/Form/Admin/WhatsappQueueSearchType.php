<?php

declare (strict_types=1);

namespace App\Form\Admin;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WhatsappQueueSearchType extends AbstractType
{
    public function __construct(
        private readonly UserRepository $userRepository,
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->setMethod('GET')
            ->add('sender', EntityType::class, [
                'mapped' => false,
                'class' => User::class,
                'choice_label' => 'fullName',
                'query_builder' => fn() => $this->userRepository->getAdmins(),
                'label' => 'Отправитель',
                'placeholder' => 'Выберите отправителя',
                'required' => false,
                'attr' => [
                    'class' => 'form-select',
                    'placeholder' => 'Отправитель',
                    'onfocus' => 'this.placeholder = ""',
                    'onblur' => 'this.placeholder = "Отправитель"',
                ],
                'label_attr' => [
                    'class' => 'col-sm-2 col-form-label'
                ],
            ])
            ->add('userName', TextType::class, [
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Фио получателя',
                    'onfocus' => 'this.placeholder = ""',
                    'onblur' => 'this.placeholder = "Фио получателя"',
                ],
                'label_attr' => [
                    'class' => 'col-sm-2 col-form-label'
                ],
            ])
            ->add('phone', TextType::class, [
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Телефон',
                    'onfocus' => 'this.placeholder = ""',
                    'onblur' => 'this.placeholder = "Телефон"',
                ],
                'label_attr' => [
                    'class' => 'col-sm-2 col-form-label'
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Поиск',
                'attr' => [
                    'class' => 'btn btn-outline-dark float-end',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => false
        ]);
    }
}
