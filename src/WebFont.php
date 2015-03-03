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

use WebfontGenerator\Util\StringHandler;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class WebFont
{
    protected $output = [];
    protected $originalFile = null;
    protected $zipFile = null;
    protected $buildDir = null;
    protected $distDir = null;
    protected $fs = null;
    protected $id = null;
    protected $outputFiles = null;

    public function __construct(UploadedFile $tmpFile, Filesystem $fs)
    {
        $this->id = uniqid();
        $this->fs = $fs;
        $this->buildDir = ROOT . "/build/".$this->id;
        $this->distDir = ROOT . "/dist";
        $this->outputFiles = [];

        if (!$this->fs->exists($this->buildDir)) {
            $this->fs->mkdir($this->buildDir);
        }
        if (!$this->fs->exists($this->distDir)) {
            $this->fs->mkdir($this->distDir);
        }

        $original = pathinfo($tmpFile->getClientOriginalName());
        $basename = StringHandler::slugify(basename($tmpFile->getClientOriginalName(), $original['extension']));

        $this->originalFile = $tmpFile->move($this->buildDir, $basename . '.' . $original['extension']);
    }

    public function getOriginal()
    {
        return $this->originalFile;
    }

    /**
     * @param Symfony\Component\HttpFoundation\File\File $file
     */
    public function addFile(File $file)
    {
        $this->outputFiles[] = $file;

        return $this;
    }

    /**
     * @return Symfony\Component\HttpFoundation\File\File
     */
    public function getZipFile()
    {
        $zipPath = $this->getZipPath();
        $zip = new \ZipArchive();
        $zip->open($zipPath, \ZipArchive::CREATE);

        $zip->addFile($this->originalFile->getRealpath(), 'original-'.$this->originalFile->getBasename());

        foreach ($this->outputFiles as $file) {
            $zip->addFile($file->getRealpath(), $file->getBasename());
        }

        if (!$zip->close()) {
            throw new \Exception("Impossible to create ZIP archive", 1);
        }

        $this->zipFile = new File($zipPath);
        $this->fs->remove($this->buildDir);

        return $this->zipFile;
    }

    public function getZipPath()
    {
        return $this->distDir .
               "/" .
               strtolower(str_replace(".", "-", $this->originalFile->getBasename())) .
               "-" .
               $this->id .
               ".zip";
    }
}
