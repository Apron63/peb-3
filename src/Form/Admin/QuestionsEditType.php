<?php

namespace App\Form\Admin;

use App\Entity\Questions;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Trsteel\CkeditorBundle\Form\Type\CkeditorType as TypeCkeditorType;

class QuestionsEditType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'required' => false,
                'attr' => [
                    'label' => false,
                    'class' => 'form-control',
                    'placeholder' => 'Номер',
                    'onfocus' => 'this.placeholder = ""',
                    'onblur' => 'this.placeholder = "Номер"',
                ],
            ])
            ->add('description', TypeCkeditorType::class, [])
            // ->add('type', ChoiceType::class, [
            //     'choices' => Questions::getAnswerType(),
            //     'attr' => [
            //         'label' => false,
            //         'class' => 'form-select',
            //         'placeholder' => 'Количество правильных ответов',
            //         'onfocus' => 'this.placeholder = ""',
            //         'onblur' => 'this.placeholder = "Количество правильных вопросов"',
            //         'rows' => 6,
            //     ],
            // ])
            ->add('help', TypeCkeditorType::class, [])
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
            'data_class' => Questions::class,
        ]);
    }
}
