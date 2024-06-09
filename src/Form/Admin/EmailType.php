<?php

namespace App\Form\Admin;

use App\Entity\MailingQueue;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Trsteel\CkeditorBundle\Form\Type\CkeditorType;

class EmailType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('id', HiddenType::class)
            ->add('reciever', TextType::class, [
                'label' => 'Получатель',
                'label_attr' => [
                    'class' => 'form-label',
                ],
                'attr' => [
                    'placeholder' => 'Укажите один или несколько адресов email, через запятую',
                    'class' => 'form-control',
                    'maxlength' => 255,
                ],
                'constraints' => [
                    new Length([
                        'max' => 255,
                    ]),
                    new NotBlank(),
                    new Callback([$this, 'checkEmails']),
                ],
            ])
            ->add('subject', TextType::class, [
                'label' => 'Тема',
                'label_attr' => [
                    'class' => 'form-label',
                ],
                'attr' => [
                    'class' => 'form-control',
                    'maxlength' => 255,
                ],
                'constraints' => [
                    new Length([
                        'max' => 255,
                    ]),
                    new NotBlank(),
                ],
            ])
            ->add('content', CkeditorType::class, [
                'label' => 'Текст письма',
                'label_attr' => [
                    'class' => 'form-label',
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Отправить',
                'attr' => [
                    'class' => 'btn btn-outline-dark',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => MailingQueue::class,
        ]);
    }

    public function checkEmails(mixed $value, ExecutionContextInterface $context, mixed $payload): void
    {
        $emailsArray = explode(',', $value);

        if (empty($emailsArray)) {
            $context->buildViolation('Не найдено ни одного email адреса')->addViolation();
        } else {
            foreach ($emailsArray as $emailAddress) {
                $checkedEmail = trim($emailAddress);

                if (! filter_var($checkedEmail, FILTER_VALIDATE_EMAIL)) {
                    $context->buildViolation($checkedEmail . ' не является правильным email адресом')->addViolation();
                }
            }
        }
    }
}
