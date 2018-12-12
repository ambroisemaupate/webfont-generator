<?php

namespace WebfontGenerator\Subsetters;

use Symfony\Component\HttpFoundation\File\File;
use WebfontGenerator\Util\StringHandler;

class PythonFontSubset
{
    protected $binPath;

    /**
     * @see http://jrgraphix.net/research/unicode.php
     * @var array
     */
    public static $ranges = [
        'basic_latin' => 'U+0000-007F',
        'latin1_supplement' => 'U+00A0-00FF',
        'latin_extended_a' => 'U+0100-017F',
        'latin_extended_b' => 'U+0180-024F',
        'ipa_extensions' => 'U+0250-02AF',
        'spacing_modifier_letters' => 'U+02B0-02FF',
        'combining_diacritical_marks' => 'U+0300-036F',
        'greek_coptic' => 'U+0370-03FF',
        'cyrillic' => 'U+0400-04FF',
        'cyrillic_supplementary' => 'U+0500-052F',
        'armenian' => 'U+0530-058F',
        'hebrew' => 'U+0590-05FF',
        'arabic' => 'U+0600-06FF',
        'syriac' => 'U+0700-074F',
        'thaana' => 'U+0780-07BF',
        'devanagari' => 'U+0900-097F',
        'bengali' => 'U+0980-09FF',
        'gurmukhi' => 'U+0A00-0A7F',
        'gujarati' => 'U+0A80-0AFF',
        'oriya' => 'U+0B00-0B7F',
        'tamil' => 'U+0B80-0BFF',
        'telugu' => 'U+0C00-0C7F',
        'kannada' => 'U+0C80-0CFF',
        'malayalam' => 'U+0D00-0D7F',
        'sinhala' => 'U+0D80-0DFF',
        'thai' => 'U+0E00-0E7F',
        'lao' => 'U+0E80-0EFF',
        'tibetan' => 'U+0F00-0FFF',
        // …
        'latin_extended_additional' => 'U+1E00-1EFF',
        'greek_extended' => 'U+1F00-1FFF',
        'general_punctuation' => 'U+2000-206F',
        'superscripts_subscripts' => 'U+2070-209F',
        'currency_symbols' => 'U+20A0-20CF',
        //…
        'number_forms' => 'U+2150-218F',
        'arrows' => 'U+2190-21FF',
        'mathematical_operators' => 'U+2200-22FF',
    ];

    public function __construct($binPath)
    {
        $this->binPath = $binPath;
    }

    /**
     * @return array
     */
    public static function getBaseSet(): array
    {
        return [
            static::getUnicodes('basic_latin'),
            static::getUnicodes('latin1_supplement'),
            static::getUnicodes('latin_extended_a'),
            static::getUnicodes('spacing_modifier_letters'),
            static::getUnicodes('combining_diacritical_marks'),
            static::getUnicodes('general_punctuation'),
            static::getUnicodes('currency_symbols'),
        ];
    }

    /**
     * @param string $set
     *
     * @return mixed
     */
    public static function getUnicodes(string $set): string
    {
        if (!array_key_exists($set, static::$ranges)) {
            throw new \InvalidArgumentException('Unicode range does not exist.');
        }
        return static::$ranges[$set];
    }

    /**
     * @param File  $input
     * @param array $unicodes
     *
     * @return File
     */
    public function subset(File $input, array $unicodes = [])
    {
        if (!file_exists($this->binPath)) {
            throw new \RuntimeException('pyftsubset binary could not be found at path '.$this->binPath);
        }
        $outFile = $this->getSubsetPath($input);

        if (count($unicodes) === 0) {
            $unicodes = $this->getBaseSet();
        }

        $cmd = $this->binPath.' "'.$input->getRealPath().'" --unicodes="'. implode(',', $unicodes).'" --output-file="'.$outFile.'"';

        exec(
            $cmd,
            $output,
            $return
        );

        if (0 !== $return) {
            throw new \RuntimeException('pyftsubset could not subset '.$input->getBasename().' font file.');
        } else {
            return new File($outFile);
        }
    }

    /**
     * @param File $input
     *
     * @return string
     */
    public function getSubsetPath(File $input)
    {
        $basename = StringHandler::slugify($input->getBasename('.'.$input->getExtension()));

        return $input->getPath().DIRECTORY_SEPARATOR.$basename.'-subset.'.$input->getExtension();
    }
}
