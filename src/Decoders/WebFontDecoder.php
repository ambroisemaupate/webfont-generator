<?php

namespace WebfontGenerator\Decoders;

use Symfony\Component\HttpFoundation\File\File;
use WebfontGenerator\Util\StringHandler;

/**
 * Decode WOFF and WOFF2 files back to TTF/OTF so they can be subset and converted.
 *
 * @package WebfontGenerator\Decoders
 */
class WebFontDecoder
{
    /**
     * Font file signatures (first 4 bytes).
     *
     * @var array
     */
    public static $signatures = [
        "\x00\x01\x00\x00" => 'ttf',
        'true' => 'ttf',
        'OTTO' => 'otf',
        'wOFF' => 'woff',
        'wOF2' => 'woff2',
    ];

    /**
     * @var array
     */
    protected $binPaths;

    /**
     * @param array $binPaths Binary paths indexed by input format (woff, woff2)
     */
    public function __construct(array $binPaths)
    {
        $this->binPaths = $binPaths;
    }

    /**
     * Detect font format from file signature.
     *
     * @param string $path
     *
     * @return string|null ttf, otf, woff, woff2 or null if not a font file.
     */
    public static function getFormat(string $path): ?string
    {
        $handle = @fopen($path, 'rb');
        if (false === $handle) {
            return null;
        }
        $signature = fread($handle, 4);
        fclose($handle);

        return static::$signatures[$signature] ?? null;
    }

    /**
     * @param File $input
     *
     * @return bool
     */
    public static function needsDecoding(File $input): bool
    {
        return in_array(strtolower($input->getExtension()), ['woff', 'woff2'], true);
    }

    /**
     * Decode a WOFF/WOFF2 file into a "source" sub-folder, so that converters
     * output files do not overwrite the original uploaded file.
     *
     * @param File $input
     *
     * @return File Decoded TTF or OTF file
     */
    public function decode(File $input): File
    {
        $format = strtolower($input->getExtension());
        $binPath = $this->binPaths[$format] ?? null;

        if (null === $binPath || !file_exists($binPath)) {
            throw new \RuntimeException('No decoder could be found for '.strtoupper($format).' files.');
        }

        $sourceDir = $input->getPath().DIRECTORY_SEPARATOR.'source';
        if (!is_dir($sourceDir)) {
            mkdir($sourceDir);
        }
        $basename = StringHandler::slugify($input->getBasename('.'.$input->getExtension()));
        $outFile = $sourceDir.DIRECTORY_SEPARATOR.$basename.'.ttf';
        $output = [];

        switch ($format) {
            case 'woff2':
                // woff2_decompress writes its output next to its input file.
                $tmpFile = $sourceDir.DIRECTORY_SEPARATOR.$basename.'.woff2';
                copy($input->getRealPath(), $tmpFile);
                exec($binPath.' '.escapeshellarg($tmpFile), $output, $return);
                unlink($tmpFile);
                break;
            case 'woff':
                exec(
                    $binPath.' '.escapeshellarg($input->getRealPath()).' > '.escapeshellarg($outFile),
                    $output,
                    $return
                );
                break;
            default:
                throw new \RuntimeException($input->getBasename().' does not need to be decoded.');
        }

        if (0 !== $return || !file_exists($outFile) || null === static::getFormat($outFile)) {
            throw new \RuntimeException(
                'Could not decode '.$input->getBasename().' from '.strtoupper($format).' format.'
            );
        }

        // WOFF and WOFF2 can wrap CFF based (OpenType) fonts.
        if ('otf' === static::getFormat($outFile)) {
            $otfFile = $sourceDir.DIRECTORY_SEPARATOR.$basename.'.otf';
            rename($outFile, $otfFile);
            $outFile = $otfFile;
        }

        return new File($outFile);
    }
}
