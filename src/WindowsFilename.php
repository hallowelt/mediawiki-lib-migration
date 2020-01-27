<?php

namespace HalloWelt\MediaWiki\Lib\Migration;

class WindowsFilename {

	protected $origFilename = '';

	protected $substitutionMap = [
		'ä' => 'ae',
		'ö' => 'oe',
		'ü' => 'ue',
		'Ä' => 'Ae',
		'Ö' => 'Oe',
		'Ü' => 'Ue',
		'ß' => 'ss',
		'á' => 'a',
		'ć' => 'c',
		'é' => 'e',
		'í' => 'i',
		'ó' => 'o',
		'ú' => 'u',
		'ź' => 'Z',
		'Á' => 'A',
		'Ć' => 'C',
		'É' => 'E',
		'Í' => 'I',
		'Ú' => 'U',
		'Ó' => 'O',
		'Ź' => 'Z',
		'ẃ' => 'w',
		'ŕ' => 'r',
		'ź' => 'z',
		'ṕ' => 'p',
		'ś' => 's',
		'ǵ' => 'g',
		'ĺ' => 'l',
		'ý' => 'y',
		'ǘ' => 'ue',
		'ń' => 'n',
		'ḿ' => 'm',
		'Ẃ' => 'W',
		'Ŕ' => 'R',
		'Ź' => 'Z',
		'Ṕ' => 'P',
		'Ś' => 'S',
		'Ǵ' => 'G',
		'Ḱ' => 'K',
		'Ĺ' => 'L',
		'Ý' => 'Y',
		'Ć' => 'C',
		'Ǘ' => 'Ue',
		'Ń' => 'N',
		'Ḿ' => 'M'
	];

	public function __construct( $filename ) {
		$this->origFilename = $filename;
	}

	public function __toString() {
		$newFilename = $this->origFilename;
		foreach( $this->substitutionMap as $nonAsciiChar => $asciiCharReplacement ) {
			$newFilename = str_replace(
				$nonAsciiChar,
				$asciiCharReplacement,
				$newFilename
			);
		}
		$newFilename = str_replace( ':',' ', $newFilename );
		//Remove all non-ascii chars; HINT: http://www.stemkoski.com/php-remove-non-ascii-characters-from-a-string/
		$newFilename = preg_replace( '/[^(\x20-\x7F)]*/','', $newFilename );
		//Remove all characters that don't work on  Windows FS
		$newFilename = str_replace( array( '/', '\\', '<', '>', '?', ':', '*', '"', '|', ';', '!' ), '', $newFilename );
		//Use MediaWiki function
		$newFilename = wfStripIllegalFilenameChars( $newFilename );

		$newFilename = preg_replace( '#  +#', ' ', $newFilename );
		$newFilename = str_replace( ' .','.', $newFilename ); //"Some file .pdf" => "Some file.pdf"
		$newFilename = str_replace( '_.','.', $newFilename ); //"Some file_.pdf" => "Some file.pdf"
		$newFilename = str_replace( ' ','_', $newFilename );

		$newFilename = trim( $newFilename, '_~' );
		$newFilename = preg_replace( '#_+#', '_', $newFilename );

		if( mb_strlen( $newFilename ) > 255 ) {
			throw new Exception( "Filename '$newFilename' exceeds maximum length of 255 characters!" );
		}

		$newFilename = ucfirst( $newFilename );

		return $newFilename;
	}
}
