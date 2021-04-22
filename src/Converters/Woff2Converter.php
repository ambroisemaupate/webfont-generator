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
 * @file Woff2Converter.php
 * @author Ambroise Maupate
 */
namespace WebfontGenerator\Converters;

use WebfontGenerator\Util\StringHandler;
use WebfontGenerator\Converters\Driver;
use Symfony\Component\HttpFoundation\File\File;

/**
 * Class Woff2Converter
 *
 * @package WebfontGenerator\Converters
 */
class Woff2Converter implements ConverterInterface
{
    protected $woffCompress = null;

    public function __construct($binPath)
    {		
		$this->driver = new Driver();
		if (('\\' === \DIRECTORY_SEPARATOR) && (PHP_OS !== 'Linux'))
		{	
			//If we use usr/bin for sfnt2woff on Windows
			if ($this->driver->file_exists($binPath))
			{
				$this->woffCompress = $binPath;
			}
			else
			{
				//Rename config-default-win.yml to config.yml
				$this->woffCompress = $binPath . '.exe';
			}		
		}
		else
		{
			//If we use usr/bin for sfnt2woff on Linux
			$this->woffCompress = $binPath;
		}
		
    }

    public function convert(File $input)
    {
        
        if (!$this->driver->file_exists($this->woffCompress)) 
		{
            throw new \RuntimeException($this->woffCompress . ' (woff2 compress) could not be found.');
        }
		
		$output = array([]);
        $outFile = $this->getWOFFPath($input);
		$inpFile = $input->getRealPath();
 		$return = 0;
		
		//Fix an error with output file on some Windows versions of sfnt2woff 
		if ($this->driver->file_exists($old_name = $inpFile . '.woff')) 
		{
			//Get new name
			$new_name = str_replace(array('.ttf', '.TTF', '.otf', '.OTF', '.svg', '.SVG'), '.woff', $inpFile);
			
			$src_dir = realpath($old_name);
			$trg_dir = realpath($new_name);
						
			if (!is_writable($trg_dir))
			{
				@chmod($trg_dir, 0777);
			}

			if (!copy($old_name, $new_name))
			{
				print(PHP_OS . ' exception ('.@is_writable($new_name).'): The converter did not recovered the woff file via rename().' . ' line: ' . __LINE__ .' file: '. __FILE__);
				
			}
			
			//copy file permissions
			if ($perm = @fileperms($src_dir))
			{
				@chmod($trg_dir, $perm);
			}
				
			if ($this->driver->file_exists($new_name))			
			{
				print(PHP_OS . ' exception ('.@is_writable($new_name).'): The converter recovered the '.basename($new_name).' woff file via rename().');
			}			
        }	
        
		exec($this->woffCompress . ' "' . $inpFile . '"', $output, $return);

        if (0 !== $return) 
		{
            throw new \RuntimeException($this->woffCompress.' "'.$input->getRealPath().'"' .
           ' ' . ' could not convert '.$input->getBasename().' to woff2 format.');
        } 
		else 
		{
            return new File($outFile);
        }
    }

    public function getWOFFPath(File $input)
    {
        $basename = StringHandler::slugify($input->getBasename('.'.$input->getExtension()));

        return $input->getPath().DIRECTORY_SEPARATOR.$basename.'.woff2';
    }
}
