<?php

namespace App\Form\Admin;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Trsteel\CkeditorBundle\Form\Type\CkeditorType as TypeCkeditorType;

class SendListToEmailType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('emails', TextType::class, [
                'label' => 'Email для отправки',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Укажите один или несколько адресов email, через запятую',
                    'onfocus' => 'this.placeholder = ""',
                    'onblur' => 'this.placeholder = "Укажите один или несколько адресов email, через запятую"',
                ],
                'label_attr' => [
                    'class' => 'form-label',
                ],
                'row_attr' => [
                    'class' => 'mb-3',
                ],
            ])
            ->add('subject', TextType::class, [
                'label' => 'Тема письма',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Укажите тему письма',
                    'onfocus' => 'this.placeholder = ""',
                    'onblur' => 'this.placeholder = "Укажите тему письма"',
                ],
                'label_attr' => [
                    'class' => 'form-label',
                ],
                'row_attr' => [
                    'class' => 'mb-3',
                ],
            ])
            ->add('comment', TypeCkeditorType::class, [
                'label' => 'Комментарий',
                'label_attr' => [
                    'class' => 'form-label',
                ],
                'row_attr' => [
                    'class' => 'mb-3',
                ],
            ])
            ->setMethod('POST');
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
