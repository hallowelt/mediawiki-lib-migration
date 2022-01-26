<?php

namespace HalloWelt\MediaWiki\Lib\Migration;

class WindowsFilename {

	/**
	 * From <mediawiki>/includes/DefaultSettings.php
	 *
	 * Additional characters that are not allowed in filenames. They are replaced with '-' when
	 * uploading. Like $wgLegalTitleChars, this is a regexp character class.
	 *
	 * Slashes and backslashes are disallowed regardless of this setting, but included here for
	 * completeness.
	 */
	protected $wgIllegalFileChars = ":\\/\\\\";

	/**
	 * From <mediawiki>/includes/DefaultSettings.php
	 *
	 * Allowed title characters -- regex character class
	 * Don't change this unless you know what you're doing
	 *
	 * Problematic punctuation:
	 *   -  []{}|#    Are needed for link syntax, never enable these
	 *   -  <>        Causes problems with HTML escaping, don't use
	 *   -  %         Enabled by default, minor problems with path to query rewrite rules, see below
	 *   -  +         Enabled by default, but doesn't work with path to query rewrite rules,
	 *                corrupted by apache
	 *   -  ?         Enabled by default, but doesn't work with path to PATH_INFO rewrites
	 *
	 * All three of these punctuation problems can be avoided by using an alias,
	 * instead of a rewrite rule of either variety.
	 *
	 * The problem with % is that when using a path to query rewrite rule, URLs are
	 * double-unescaped: once by Apache's path conversion code, and again by PHP. So
	 * %253F, for example, becomes "?". Our code does not double-escape to compensate
	 * for this, indeed double escaping would break if the double-escaped title was
	 * passed in the query string rather than the path. This is a minor security issue
	 * because articles can be created such that they are hard to view or edit.
	 *
	 * In some rare cases you may wish to remove + for compatibility with old links.
	 */
	protected $wgLegalTitleChars = " %!\"$&'()*,\\-.\\/0-9:;=?@A-Z\\\\^_`a-z~\\x80-\\xFF+";

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

	/**
	 *
	 * @param type $filename
	 */
	public function __construct( $filename ) {
		$this->origFilename = $filename;
	}

	/**
	 *
	 * @return string
	 */
	public function __toString() {
		$newFilename = $this->origFilename;
		foreach ( $this->substitutionMap as $nonAsciiChar => $asciiCharReplacement ) {
			$newFilename = str_replace(
				$nonAsciiChar,
				$asciiCharReplacement,
				$newFilename
			);
		}
		$newFilename = str_replace( ':', ' ', $newFilename );
		// Remove all non-ascii chars; HINT: http://www.stemkoski.com/php-remove-non-ascii-characters-from-a-string/
		$newFilename = preg_replace( '/[^(\x20-\x7F)]*/', '', $newFilename );
		// Remove all characters that don't work on  Windows FS
		$newFilename = str_replace( [ '/', '\\', '<', '>', '?', ':', '*', '"', '|', ';', '!' ], '', $newFilename );
		// Use MediaWiki function
		$newFilename = $this->wfStripIllegalFilenameChars( $newFilename );

		$newFilename = preg_replace( '#  +#', ' ', $newFilename );
		// "Some file .pdf" => "Some file.pdf"
		$newFilename = str_replace( ' .', '.', $newFilename );
		// "Some file_.pdf" => "Some file.pdf"
		$newFilename = str_replace( '_.', '.', $newFilename );
		$newFilename = str_replace( ' ', '_', $newFilename );

		$newFilename = trim( $newFilename, '_~' );
		$newFilename = preg_replace( '#_+#', '_', $newFilename );

		if ( mb_strlen( $newFilename ) > 255 ) {
			throw new InvalidTitleException(
				$newFilename,
				"Filename '$newFilename' exceeds maximum length of 255 characters!"
			);
		}

		$newFilename = ucfirst( $newFilename );

		return $newFilename;
	}

	/**
	 * From <mediawiki>/includes/GlobalFunctions.php
	 *
	 * Replace all invalid characters with '-'.
	 * Additional characters can be defined in $wgIllegalFileChars (see T22489).
	 * By default, $wgIllegalFileChars includes ':', '/', '\'.
	 *
	 * @param string $name Filename to process
	 * @return string
	 */
	private function wfStripIllegalFilenameChars( $name ) {
		$illegalFileChars = "|[" . $this->wgIllegalFileChars . "]";
		$name = preg_replace(
			"/[^" . $this->wgLegalTitleChars . "]" . $illegalFileChars . "/",
			'-',
			$name
		);
		// $wgIllegalFileChars may not include '/' and '\', so we still need to do this
		$name = $this->wfBaseName( $name );
		return $name;
	}

	/**
	 * From <mediawiki>/includes/GlobalFunctions.php
	 *
	 * Return the final portion of a pathname.
	 * Reimplemented because PHP5's "basename()" is buggy with multibyte text.
	 * https://bugs.php.net/bug.php?id=33898
	 *
	 * PHP's basename() only considers '\' a pathchar on Windows and Netware.
	 * We'll consider it so always, as we don't want '\s' in our Unix paths either.
	 *
	 * @param string $path
	 * @param string $suffix String to remove if present
	 * @return string
	 */
	private function wfBaseName( $path, $suffix = '' ) {
		if ( $suffix == '' ) {
			$encSuffix = '';
		} else {
			$encSuffix = '(?:' . preg_quote( $suffix, '#' ) . ')?';
		}

		$matches = [];
		if ( preg_match( "#([^/\\\\]*?){$encSuffix}[/\\\\]*$#", $path, $matches ) ) {
			return $matches[1];
		} else {
			return '';
		}
	}
}
