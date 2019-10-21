<?php

namespace HalloWelt\MediaWiki\Lib\Migration;

use Exception;

class TitleBuilder {

    /**
     * array
     */
    private $namespaceMap = [];

    /**
     * @var string
     */
    private $namespacePrefix = '';

    private $titleSegments = [];

    public function __constructor( array $namespaceMap ) {
        $this->namespaceMap = $namespaceMap;
    }

    public function setNamespace( int $namespaceId ) : TitleBuilder {
        if( !isset( $this->namespaceMap[$namespaceId] ) ) {
            throw new Exception( "No prefix set for `$namespaceId`!" );
        }
        $this->namespacePrefix = $this->namespaceMap[$namespaceId];
        return $this;
    }

    public function appendTitleSegment( $segment ) : TitleBuilder {
        $cleanedSegment = $this->cleanTitleSegment( $segment );
        if( empty( $cleanedSegment ) ) {
            return $this;
            #throw new Exception( "Cleaned version of `$segment` is empty!" );
        }
        $this->titleSegments[] = $cleanedSegment;
        return $this;
    }

    public function reduceTitleSegments() : TitleBuilder {
        $this->titleSegments = array_unique( array_values( $this->titleSegments ) );
        return $this;
    }

    public function invertTitleSegments() : TitleBuilder {
        $this->titleSegments = array_reverse( $this->titleSegments );
        return $this;
    }

    public function build() : string {
        $prefix = '';
        if( !empty( $this->namespacePrefix ) ) {
            $prefix = $this->namespacePrefix . ':';
        }
        if( empty( $this->titleSegments ) ) {
            throw new Exception( "No title segments set" );
        }

        $titleText = implode( '/', $this->titleSegments );

        if( mb_strlen( $titleText ) > 255 ) {
			throw new Exception(
				"Title '$titleText' exceeds maximum length of 255 chars!"
			);
		}

        return $prefix.$titleText;
    }

    private function cleanTitleSegment( $segment ) {
        $segment = str_replace( ' ', '_', $segment );
        $segment = preg_replace( static::getTitleInvalidRegex(), '_',  $segment );
        //MediaWiki normalizes multiple spaces/undescores into one single underscore
		$segment = preg_replace('#_+#si', '_',  $segment );
        $segment = trim( $segment, ' _\t');
        return trim( $segment );
    }

    /**
     * See 
     * - https://github.com/wikimedia/mediawiki/blob/05ce3b7740951cb26b29bbe3ac9deb610541df48/includes/title/MediaWikiTitleCodec.php#L511-L538
     * - https://github.com/wikimedia/mediawiki/blob/05ce3b7740951cb26b29bbe3ac9deb610541df48/includes/DefaultSettings.php#L3901-L3925
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