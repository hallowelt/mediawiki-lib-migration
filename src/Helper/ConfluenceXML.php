<?php

namespace HalloWelt\MediaWiki\Lib\Migration\Helper;

use DOMDocument;
use DOMElement;
use DOMNodeList;
use DOMXPath;

class ConfluenceXML {

	/**
	 *
	 * @var DOMDocument
	 */
	protected $dom = null;

	/**
	 *
	 * @var DOMXPath
	 */
	protected $xpath = null;

	public function __construct( $dom ) {
		$this->dom = $dom;
		$this->xpath = new DOMXPath( $this->dom );
	}

	/**
	 *
	 * @return DOMXPath
	 */
	public function getXPath() {
		return $this->xpath;
	}

	/**
	 * Returns the integert ID of an model entity in Confluence export XML
	 * @param DOMElement $domNode
	 * @return int
	 * @throws UnexpectedValueException
	 */
	public function getIDNodeValue( $domNode ) {
		//TODO: Use XPath to query only direct children!
		$oId = $domNode->getElementsByTagName('id')->item(0);
		if( $oId instanceof DOMElement === false ) {
			throw new UnexpectedValueException( 'No ID element found!' );
		}

		return (int) $oId->nodeValue;
	}

	/**
	 *
	 * @param string $propName
	 * @param DOMElement $contextElement
	 * @return DOMNodeList
	 */
	public function getPropertyNodes( $propName, $contextElement = null ) {
		if( $contextElement === null ) {
			//Fetch all in whole document
			return $this->xpath->query( '//property[@name="'.$propName.'"]' );
		}

		//Fetch only direct children from context
		return $this->xpath->query( './property[@name="'.$propName.'"]', $contextElement );
	}

	/**
	 *
	 * @param string $propName
	 * @param DOMElement $contextElement
	 * @return DOMElement
	 */
	public function getPropertyNode( $propName, $contextElement = null ) {
		return $this->getPropertyNodes($propName, $contextElement)->item(0);
	}



	/**
	 * There are some classes of <property> elements that do contain the value
	 * directly as nodeValue but instead contain an aditional <id> element that
	 * references another element in the XML
	 *
	 * @var array
	 */
	protected $propertyClassesOfTypeIDRef = array(
		'Space', 'Page', 'ConfluenceUserImpl', 'Attachment', 'Label'
	);

	/**
	 *
	 * @param string $propertyName
	 * @param DOMElement $contextElement
	 * @return string
	 */
	public function getPropertyValue( $propertyName, $contextElement, $default = '' ) {
		$propertyNode = $this->getPropertyNode( $propertyName, $contextElement );
		if( $propertyNode instanceof DOMElement == false ) {
			/*error_log(
				'Node "'.$contextElement->getNodePath().
					'" contains no property "'.$propertyName.'"!'
			);*/
			return $default;
		}
		$classValue = $propertyNode->getAttribute('class');
		if( in_array( $classValue, $this->propertyClassesOfTypeIDRef ) ) {
			return $this->getIDNodeValue( $propertyNode );
		}

		return $propertyNode->nodeValue;
	}

	/**
	 *
	 * @param string $objectNodeClass e.g. 'Space', 'Page', 'Attachment',
	 * 'BodyContent', 'ConfluenceUserImpl', ...
	 * @return DOMNodeList
	 */
	public function getObjectNodes( $objectNodeClass ) {
		return $this->xpath->query('//object[@class="'.$objectNodeClass.'"]');
	}

	/**
	 *
	 * @param int $id
	 * @param string $objectNodeClass
	 * @return DOMElement
	 */
	public function getObjectNodeById( $id, $objectNodeClass ) {
		$xpath = "//object[@class='$objectNodeClass' and id='$id']";
		return $this->xpath->query( $xpath )->item(0);
	}

	/**
	 *
	 * @param string $collectionName
	 * @param DOMElement $contextElement
	 * @return DOMNodeList
	 */
	public function getElementsFromCollection( $collectionName, $contextElement ) {
		return $this->xpath->query( './collection[@name="'.$collectionName.'"]/element', $contextElement );
	}
}