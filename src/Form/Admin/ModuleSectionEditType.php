<?php

namespace App\Form\Admin;

use App\Entity\ModuleSection;
use App\Service\ModuleSectionArrowsService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class ModuleSectionEditType extends AbstractType
{
    public function __construct(
        private readonly ModuleSectionArrowsService $moduleSectionArrowsService,
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $course = $options['data']->getModule()->getCourse();
        $moduleSections = $this->moduleSectionArrowsService->getModuleSectionList($course);

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
            ->add('type', ChoiceType::class, [
                'label' => 'Вид страницы',
                'choices' => ModuleSection::PAGE_TYPES,
                'attr' => [
                    'class' => 'form-select',
                    'placeholder' => 'Тип раздела',
                    'onfocus' => 'this.placeholder = ""',
                    'onblur' => 'this.placeholder = "Тип раздела"',
                ],
                'label_attr' => [
                    'class' => 'col-sm-2 col-form-label'
                ],
            ])
            ->add('prevMaterialId', ChoiceType::class, [
                'label' => 'Предыдущий материал',
                'choices' => $moduleSections,
                'group_by' => function ($choice) {
                    return $this->moduleSectionArrowsService->getModuleSectionGroup($choice);
                },
                'attr' => [
                    'class' => 'form-select',
                    'placeholder' => 'Предыдущий материал',
                    'onfocus' => 'this.placeholder = ""',
                    'onblur' => 'this.placeholder = "Предыдущий материал"',
                ],
                'label_attr' => [
                    'class' => 'col-sm-2 col-form-label'
                ],
            ])
            ->add('nextMaterialId', ChoiceType::class, [
                'label' => 'Следующий материал',
                'choices' => $moduleSections,
                'group_by' => function ($choice) {
                    return $this->moduleSectionArrowsService->getModuleSectionGroup($choice);
                },
                'attr' => [
                    'class' => 'form-select',
                    'placeholder' => 'Следующий материал',
                    'onfocus' => 'this.placeholder = ""',
                    'onblur' => 'this.placeholder = "Следующий материал"',
                ],
                'label_attr' => [
                    'class' => 'col-sm-2 col-form-label'
                ],
            ])
            ->add('finalTestingIsNext', CheckboxType::class, [
                'label' => 'Переход к итоговому тестированию',
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input',
                ],
                'label_attr' => [
                    'class' => 'form-check-label',
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
