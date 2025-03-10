<?php

declare(strict_types=1);

namespace App\Form\Admin;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Validator\Constraints\Email;

class UserEditType extends AbstractType
{
    private TokenStorageInterface $token;

    public function __construct(TokenStorageInterface $token)
    {
        $this->token = $token;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $systemUser = $this->token->getToken()->getUser();
        $userRoles = $options['data']->getRoles();

        $builder
            ->add('login', TextType::class, [
                'required' => false,
                'label' => 'Логин',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Логин',
                    'onfocus' => 'this.placeholder = ""',
                    'onblur' => 'this.placeholder = "Логин"',
                ],
                'label_attr' => [
                    'class' => 'col-sm-2 col-form-label'
                ],
            ])
            ->add('lastName', TextType::class, [
                'required' => false,
                'label' => 'Фамилия',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Фамилия',
                    'onfocus' => 'this.placeholder = ""',
                    'onblur' => 'this.placeholder = "Фамилия"',
                ],
                'label_attr' => [
                    'class' => 'col-sm-2 col-form-label'
                ],
            ])->add('lastName', TextType::class, [
                'required' => true,
                'label' => 'Фамилия',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Фамилия',
                    'onfocus' => 'this.placeholder = ""',
                    'onblur' => 'this.placeholder = "Фамилия"',
                ],
                'label_attr' => [
                    'class' => 'col-sm-2 col-form-label'
                ],
            ])
            ->add('firstName', TextType::class, [
                'required' => true,
                'label' => 'Имя',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Имя',
                    'onfocus' => 'this.placeholder = ""',
                    'onblur' => 'this.placeholder = "Имя"',
                ],
                'label_attr' => [
                    'class' => 'col-sm-2 col-form-label'
                ],
            ])
            ->add('patronymic', TextType::class, [
                'required' => false,
                'label' => 'Отчество',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Отчество',
                    'onfocus' => 'this.placeholder = ""',
                    'onblur' => 'this.placeholder = "Отчество"',
                ],
                'label_attr' => [
                    'class' => 'col-sm-2 col-form-label'
                ],
            ])
            ->add('organization', TextType::class, [
                'required' => true,
                'label' => 'Организация',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Организация',
                    'onfocus' => 'this.placeholder = ""',
                    'onblur' => 'this.placeholder = "Организация"',
                ],
                'label_attr' => [
                    'class' => 'col-sm-2 col-form-label'
                ],
            ])
            ->add('position', TextType::class, [
                'required' => false,
                'label' => 'Должность',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Должность',
                    'onfocus' => 'this.placeholder = ""',
                    'onblur' => 'this.placeholder = "Должность"',
                ],
                'label_attr' => [
                    'class' => 'col-sm-2 col-form-label'
                ],
            ])
            ->add('email', EmailType::class, [
                'required' => false,
                'label' => 'E-mail',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'E-mail',
                    'onfocus' => 'this.placeholder = ""',
                    'onblur' => 'this.placeholder = "E-mail"',
                ],
                'label_attr' => [
                    'class' => 'col-sm-2 col-form-label'
                ],
                'constraints' => [
                    new Email(),
                ],
            ])
            ->add('contact', TextType::class, [
                'required' => false,
                'label' => 'Контакты',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Контакты',
                    'onfocus' => 'this.placeholder = ""',
                    'onblur' => 'this.placeholder = "Контакты"',
                ],
                'label_attr' => [
                    'class' => 'col-sm-2 col-form-label'
                ],
            ])
            ->add('mobilePhone', TextType::class, [
                'required' => false,
                'label' => 'Мобильный',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '+7 XXX XXX XXXX',
                    'onfocus' => 'this.placeholder = ""',
                    'onblur' => 'this.placeholder = "Контакты"',
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

        if (
            null !== $options['data']->getId()
            && (
                in_array('ROLE_SUPER_ADMIN', $systemUser->getRoles())
                || ! (in_array('ROLE_ADMIN', $userRoles) || in_array('ROLE_SUPER_ADMIN', $userRoles))

            )
        ) {
            $builder
                ->add('plainPassword', TextType::class, [
                    'label' => 'Пароль',
                    'required' => false,
                    'attr' => [
                        'class' => 'form-control',
                        'placeholder' => 'Пароль',
                        'onfocus' => 'this.placeholder = ""',
                        'onblur' => 'this.placeholder = "Пароль"',
                    ],
                    'label_attr' => [
                        'class' => 'col-sm-2 col-form-label'
                    ],
                ]);
        }

        if (in_array(User::ROLE_SUPER_ADMIN, array_values($systemUser->getRoles()), true)) {
            $builder
                ->add('roles', ChoiceType::class, [
                    'label' => 'Доступ',
                    'choices' => [
                        'Слушатель' => User::ROLE_STUDENT,
                        //'Менеджер' => User::ROLE_USER_MANAGER,
                        'Администратор' => User::ROLE_ADMIN,
                        'Суперадминистратор' => User::ROLE_SUPER_ADMIN,
                    ],
                    'attr' => [
                        'class' => 'form-select',
                        'placeholder' => 'Доступ',
                        'onfocus' => 'this.placeholder = ""',
                        'onblur' => 'this.placeholder = "Доступ"',
                    ],
                    'label_attr' => [
                        'class' => 'col-sm-2 col-form-label'
                    ],
                ])
                ->add('active', CheckboxType::class, [
                    'label' => 'Активный',
                    'required' => false,
                    'attr' => [
                        'class' => 'form-check-input',
                        'placeholder' => 'Активный',
                        'onfocus' => 'this.placeholder = ""',
                        'onblur' => 'this.placeholder = "Активный"',
                    ],
                    'label_attr' => [
                        'class' => 'form-check-label'
                    ],
                ])
                ->add('nameLess', CheckboxType::class, [
                    'label' => 'Безымянный',
                    'required' => false,
                    'attr' => [
                        'class' => 'form-check-input',
                        'placeholder' => 'Безымянный',
                        'onfocus' => 'this.placeholder = ""',
                        'onblur' => 'this.placeholder = "Безымянный"',
                    ],
                    'label_attr' => [
                        'class' => 'form-check-label'
                    ],
                ])
                ->add('whatsappConfirmed', CheckboxType::class, [
                    'label' => 'Разрешение на рассылку WhatsApp',
                    'required' => false,
                    'attr' => [
                        'class' => 'form-check-input',
                        'placeholder' => 'Разрешение на рассылку WhatsApp',
                        'onfocus' => 'this.placeholder = ""',
                        'onblur' => 'this.placeholder = "Разрешение на рассылку WhatsApp"',
                    ],
                    'label_attr' => [
                        'class' => 'form-check-label'
                    ],
                ]);

            $builder->get('roles')
                ->addModelTransformer(new CallbackTransformer(
                    // При загрузке из БД
                        function ($rolesAsArray) {
                            // Если несколько ролей, убираем роль юзера.
                            if (count($rolesAsArray) > 1) {
                                $rolesAsArray = array_filter($rolesAsArray, static function ($value) {
                                    return $value !== User::ROLE_USER;
                                });
                            }
                            return implode(', ', $rolesAsArray);
                        },
                        // При записи в БД
                        function ($rolesAsString) {
                            return explode(', ', $rolesAsString);
                        }
                    )
                );
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
