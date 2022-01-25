<?php

namespace HalloWelt\MediaWiki\Lib\Migration;

class TitleBuilder {

	public const NS_MEDIA = -2;
	public const NS_SPECIAL = -1;

	public const NS_MAIN = 0;
	public const NS_TALK = 1;
	public const NS_USER = 2;
	public const NS_USER_TALK = 3;
	public const NS_PROJECT = 4;
	public const NS_PROJECT_TALK = 5;
	public const NS_FILE = 6;
	public const NS_FILE_TALK = 7;
	public const NS_MEDIAWIKI = 8;
	public const NS_MEDIAWIKI_TALK = 9;
	public const NS_TEMPLATE = 10;
	public const NS_TEMPLATE_TALK = 11;
	public const NS_HELP = 12;
	public const NS_HELP_TALK = 13;
	public const NS_CATEGORY = 14;
	public const NS_CATEGORY_TALK = 15;

	public const NS_FORM = 106;
	public const NS_FORM_TALK = 107;

	public const NS_PROPERTY = 102;
	public const NS_PROPERTY_TALK = 103;

	public const NS_MODULE = 828;
	public const NS_MODULE_TALK = 829;

	public const NS_BOOK = 1504;
	public const NS_BOOK_TALK = 1505;

	/**
	 * For migration purposes we always rely on the "canonical" (englisch) namespace prefixes.
	 * array
	 */
	private $namespaceMap = [];

	/**
	 * @var string
	 */
	private $namespacePrefix = '';

	/**
	 *
	 * @var string[]
	 */
	private $titleSegments = [];

	/**
	 *
	 * @param array $namespaceMap
	 */
	public function __construct( array $namespaceMap ) {
		$this->namespaceMap = [
			static::NS_MEDIA => 'Media',
			static::NS_SPECIAL => 'Special',

			static::NS_MAIN => '',
			static::NS_TALK => 'Talk',
			static::NS_USER => 'User',
			static::NS_USER_TALK => 'User_talk',
			static::NS_PROJECT => 'Project',
			static::NS_PROJECT_TALK => 'Project_talk',
			static::NS_FILE => 'File',
			static::NS_FILE_TALK => 'File_talk',
			static::NS_MEDIAWIKI => 'MediaWiki',
			static::NS_MEDIAWIKI_TALK => 'MediaWiki_talk',
			static::NS_TEMPLATE => 'Template',
			static::NS_TEMPLATE_TALK => 'Template_talk',
			static::NS_HELP => 'Help',
			static::NS_HELP_TALK => 'Help_talk',
			static::NS_CATEGORY => 'Category',
			static::NS_CATEGORY_TALK => 'Category_talk',

			static::NS_FORM => 'Form',
			static::NS_FORM_TALK => 'Form_talk',

			static::NS_PROPERTY => 'Property',
			static::NS_PROPERTY_TALK => 'Property_talk',

			static::NS_MODULE => 'Module',
			static::NS_MODULE_TALK => 'Module_talk',

			static::NS_BOOK => 'Book',
			static::NS_BOOK_TALK => 'Book_talk'
		];

		$this->namespaceMap = $this->namespaceMap + $namespaceMap;
	}

	/**
	 *
	 * @param int $namespaceId
	 * @return TitleBuilder
	 */
	public function setNamespace( int $namespaceId ) : TitleBuilder {
		$this->namespacePrefix = (string)$namespaceId;
		if ( isset( $this->namespaceMap[$namespaceId] ) ) {
			$this->namespacePrefix = $this->namespaceMap[$namespaceId];
		}

		return $this;
	}

	/**
	 *
	 * @param string $segment
	 * @return TitleBuilder
	 */
	public function appendTitleSegment( $segment ) : TitleBuilder {
		$cleanedSegment = $this->cleanTitleSegment( $segment );
		if ( empty( $cleanedSegment ) ) {
			return $this;
		}
		$this->titleSegments[] = $cleanedSegment;
		return $this;
	}

	/**
	 *
	 * @return TitleBuilder
	 */
	public function reduceTitleSegments() : TitleBuilder {
		$this->titleSegments = array_unique( array_values( $this->titleSegments ) );
		return $this;
	}

	/**
	 *
	 * @return TitleBuilder
	 */
	public function invertTitleSegments() : TitleBuilder {
		$this->titleSegments = array_reverse( $this->titleSegments );
		return $this;
	}

	/**
	 * @return string
	 * @throws InvalidTitleException
	 */
	public function build() : string {
		$prefix = '';
		if ( !empty( $this->namespacePrefix ) ) {
			$prefix = $this->namespacePrefix . ':';
		}
		if ( empty( $this->titleSegments ) ) {
			throw new InvalidTitleException( '', "No title segments set" );
		}

		$titleText = implode( '/', $this->titleSegments );
		$titleText = trim( $titleText, " \t\n\r\0\x0B/" );

		if ( mb_strlen( $titleText ) > 255 ) {
			throw new InvalidTitleException(
				$titleText,
				"Title '$titleText' exceeds maximum length of 255 chars!"
			);
		}

		return $prefix . $titleText;
	}

	/**
	 *
	 * @param string $segment
	 * @return string
	 */
	private function cleanTitleSegment( $segment ) {
		$segmentParts = explode( '/', $segment );
		$segmentParts = array_map( 'trim', $segmentParts );
		$segment = implode( ', ', $segmentParts );
		$segment = str_replace( ' ', '_', $segment );
		$segment = preg_replace( static::getTitleInvalidRegex(), '_',  $segment );
		// Slash is usually a legal char, but not in the segment
		$segment = preg_replace( '/\\//', '_', $segment );
		// MediaWiki normalizes multiple spaces/undescores into one single underscore
		$segment = preg_replace( '#_+#si', '_',  $segment );
		$segment = trim( $segment, " _\t" );
		return trim( $segment );
	}

	/**
	 * See
	 * - https://github.com/wikimedia/mediawiki/blob/05ce3b7740951cb26b29bbe3ac9deb610541df48/includes/title/MediaWikiTitleCodec.php#L511-L538
	 * - https://github.com/wikimedia/mediawiki/blob/05ce3b7740951cb26b29bbe3ac9deb610541df48/includes/DefaultSettings.php#L3901-L3925
	 *
	 * @return string
	 */
	public static function getTitleInvalidRegex() {
		static $rxTc = false;
		if ( !$rxTc ) {
			# Matching titles will be held as illegal.
			$rxTc = '/' .
				# Any character not allowed is forbidden...
				"[^ %!\"$&'()*,\\-.\\/0-9:;=?@A-Z\\\\^_`a-z~\\x80-\\xFF+]" .
				# URL percent encoding sequences interfere with the ability
				# to round-trip titles -- you can't link to them consistently.
				'|%[0-9A-Fa-f]{2}' .
				# XML/HTML character references produce similar issues.
				'|&[A-Za-z0-9\x80-\xff]+;' .
				'|&#[0-9]+;' .
				'|&#x[0-9A-Fa-f]+;' .
				'/S';
		}
		return $rxTc;
	}
}
