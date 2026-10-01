<?php
namespace WebfontGenerator\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use WebfontGenerator\Decoders\WebFontDecoder;
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
                                'maxSize' => '2M',
                            ]),
                            new Callback([$this, 'validateFontFile']),
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

    /**
     * Check file extension and signature instead of mime type,
     * which is not reliably detected for font files.
     *
     * @param mixed                     $file
     * @param ExecutionContextInterface $context
     */
    public function validateFontFile($file, ExecutionContextInterface $context)
    {
        if (!$file instanceof UploadedFile || !$file->isValid()) {
            return;
        }
        $extension = strtolower($file->getClientOriginalExtension());
        $format = WebFontDecoder::getFormat($file->getPathname());

        // OTF files may contain TrueType outlines (and vice versa)
        $sfnt = ['ttf', 'otf'];
        $isValid = in_array($extension, $sfnt, true) ?
            in_array($format, $sfnt, true) :
            in_array($extension, ['woff', 'woff2'], true) && $format === $extension;

        if (!$isValid) {
            $context->buildViolation('Only TTF, OTF, WOFF or WOFF2 files are allowed.')->addViolation();
        }
    }
}
