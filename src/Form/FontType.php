<?php
namespace WebfontGenerator\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Class FontType
 *
 * @package WebfontGenerator\Form
 */
class FontType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('files', FileType::class, [
                'multiple' => true,
                'constraints' => [
                    new NotBlank(),
                    new All([
                        'constraints' => [
                            new File([
                                'maxSize' => '2M',
                                'mimeTypes' => [
                                    'application/x-font-ttf',
                                    'application/vnd.ms-opentype'
                                ]
                            ])
                        ]
                    ])
                ]
            ])
            ->add('subset_latin', CheckboxType::class, [
                'label' => 'Subset fonts to Latin range',
                'help' => 'Only export glyphs within unicode 0000 to 05FF.',
                'required' => false,
                'attr' => ['class' => 'uk-checkbox']
            ])
        ;
    }
}
