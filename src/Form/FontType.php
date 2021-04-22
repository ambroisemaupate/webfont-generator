<?php
namespace WebfontGenerator\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;
use WebfontGenerator\Subsetters\PythonFontSubset;

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
                                'maxSize' => '52M',
                                'mimeTypes' => [
                                    'application/x-font-ttf',
                                    'application/vnd.ms-opentype',
									'image/svg+xml',
                                ]
                            ])
                        ]
                    ])
                ]
            ])
            ->add('subset_latin', CheckboxType::class, [
                'label' => 'Subset fonts to Latin range',
                'help' => 'Only export glyphs within selected ranges.',
                'required' => false,
                'attr' => ['class' => 'uk-checkbox']
            ])
            ->add('subset_ranges', ChoiceType::class, [
                'label' => 'Subset ranges',
                'help' => 'Choose your Unicode ranges (http://jrgraphix.net/research/unicode.php).',
                'choices' => PythonFontSubset::$ranges,
                'multiple' => true,
                'expanded' => true,
            ])
        ;
    }
}
