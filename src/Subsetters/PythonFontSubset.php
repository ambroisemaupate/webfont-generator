<?php

namespace WebfontGenerator\Subsetters;

use Symfony\Component\HttpFoundation\File\File;
use WebfontGenerator\Util\StringHandler;

class PythonFontSubset
{
    protected $binPath;

    public function __construct($binPath)
    {
        $this->binPath = $binPath;
    }

    public function subset(File $input)
    {
        if (!file_exists($this->binPath)) {
            throw new \RuntimeException('pyftsubset binary could not be found at path '.$this->binPath);
        }
        $outFile = $this->getSubsetPath($input);

        exec(
            $this->binPath.' "'.$input->getRealPath().'" --unicodes="U+0000-05FF" --output-file="'.$outFile.'"',
            $output,
            $return
        );

        if (0 !== $return) {
            throw new \RuntimeException('pyftsubset could not subset '.$input->getBasename().' font file.');
        } else {
            return new File($outFile);
        }
    }

    public function getSubsetPath(File $input)
    {
        $basename = StringHandler::slugify($input->getBasename('.'.$input->getExtension()));

        return $input->getPath().DIRECTORY_SEPARATOR.$basename.'-subset.'.$input->getExtension();
    }
}
