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
 * @file DriverInterface.php
 * @author Ambroise Maupate
 */
namespace WebfontGenerator\Converters;

/**
 * Interface ConverterInterface
 *
 * @package WebfontGenerator\Converters
 */
class Driver implements DriverInterface
{
	/**
	 * Checks the existence of files and liks .
	 *
	 * @param string|array|\Traversable	$files to check
	 * We should use symfony_filesystem->exists(array())  
	 * but now no need to check only one file at a time
	 * added by orynider@github.com or @gmail.com
	 * @return bool	Returns true if file exist, otherwise returns false
	 */
	public function file_exists($file)
	{
		if (@function_exists('fopen') && @fread(@fopen($file, 'r'), @filesize($file)))			
		{
			return true;
		}
		
		if (!file_exists($file))
		{
			return false;
		}
		
		return true;			
	}
}
