<?php
/**
 * Copyright © 2015, Ambroise Maupate
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * @file WebFont.php
 * @author Ambroise Maupate
 */
namespace WebfontGenerator;

use WebfontGenerator\Converters\ConverterInterface;
use WebfontGenerator\Subsetters\PythonFontSubset;
use WebfontGenerator\Util\StringHandler;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Class WebFont
 *
 * @package WebfontGenerator
 */
class WebFont
{
    protected $output = [];
    protected $originalFiles = [];
    protected $zipFile = null;
    protected $buildDir = null;
    protected $distDir = null;
    protected $fs = null;
    protected $id = null;
    protected $outputFiles = null;
    /**
     * @var ConverterInterface[]
     */
    protected $converters;
    /**
     * @var null|PythonFontSubset
     */
    protected $fontSubset;
    /**
     * @var array
     */
    protected $unicodeRanges;

    /**
     * WebFont constructor.
     *
     * @param Filesystem            $fs
     * @param array                 $converters
     * @param PythonFontSubset|null $fontSubset
     * @param array                 $unicodeRanges
     */
    public function __construct(Filesystem $fs, array $converters, PythonFontSubset $fontSubset = null, $unicodeRanges = [])
	{
        $this->id = uniqid();
        $this->originalFiles = [];
        $this->fs = $fs;
        $this->buildDir = ROOT . "/build/".$this->id;
        $this->distDir = ROOT . "/dist";
        $this->outputFiles = [];
        $this->converters = $converters;
        $this->fontSubset = $fontSubset;
        $this->unicodeRanges = $unicodeRanges;

		if (!$this->fs->exists($this->buildDir)) 
		{
            $this->fs->mkdir($this->buildDir);
        }
		
		if (!$this->fs->exists($this->distDir)) 
		{
            $this->fs->mkdir($this->distDir);
        }
	}

    /**
     * @param UploadedFile $tmpFile
     */
    public function addFontFile(UploadedFile $tmpFile)
    {
        $original = pathinfo($tmpFile->getClientOriginalName());
        $basename = StringHandler::slugify(basename($tmpFile->getClientOriginalName(), $original['extension']));
        $this->originalFiles[] = $tmpFile->move($this->buildDir, $basename . '.' . $original['extension']);
    }

    /**
     * @return null|File
     */
    public function getFirstOriginalFile()
    {
        return count($this->originalFiles) > 0 ? $this->originalFiles[0] : null;
    }

    /**
     * @return array
     */
    public function getOriginalFiles()
    {
        return $this->originalFiles;
    }

    /**
     * @param File $file
     * @return WebFont
     */
    protected function addOutputFile(File $file)
    {
        $this->outputFiles[] = $file;
        return $this;
    }

    /**
     * @return File
     * @throws \Exception
     */
    public function getZipFile()
    {
        $zipPath = $this->getZipPath();
        $zip = new \ZipArchive();
        $zip->open($zipPath, \ZipArchive::CREATE);

        foreach ($this->getOriginalFiles() as $originalFile) 
		{
            $zip->addFile($originalFile->getRealpath(), 'original-'.$originalFile->getBasename());
        }

        foreach ($this->outputFiles as $file) 
		{
            $zip->addFile($file->getRealpath(), $file->getBasename());
        }

        if (!$zip->close()) 
		{
            throw new \Exception("Impossible to create ZIP archive", 1);
        }

        $this->zipFile = new File($zipPath);
        $this->fs->remove($this->buildDir);
        return $this->zipFile;
    }

    /**
     * @return string
     */
    public function getZipPath()
    {
        if (null === $this->getFirstOriginalFile()) 
		{
            throw new \RuntimeException('No font file have been added yet.');
        }
        return $this->distDir .
               DIRECTORY_SEPARATOR .
               strtolower(str_replace(".", "-", $this->getFirstOriginalFile()->getBasename())) .
               "-" .
               $this->id .
               ".zip";
    }

    /**
     *
     */
	public function convert()
	{
		foreach ($this->getOriginalFiles() as $originalFile) 
		{
			foreach ($this->converters as $converter) 
			{
				$this->addOutputFile($converter->convert($originalFile));
			}
		}
	}

    /**
     *
     */
	public function subsetAndConvert()
	{
		if ($this->fontSubset === null) 
		{
            throw new \RuntimeException('Cannot subset with null Font Subsetter.');
		}
		
		foreach ($this->getOriginalFiles() as $originalFile) 
		{
            $subsetFont = $this->fontSubset->subset($originalFile, $this->unicodeRanges);
			
            foreach ($this->converters as $converter) 
			{
                $this->addOutputFile($converter->convert($subsetFont));
            }
		}
	}
}
