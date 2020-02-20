<?php

namespace HalloWelt\MediaWiki\Lib\Migration;

use SplFileInfo;

class Workspace {

	/**
	 *
	 * @var protected
	 */
	private $workspaceDir = null;

	/**
	 *
	 * @param SplFileInfo $workspaceDir
	 */
	public function __construct( SplFileInfo $workspaceDir ) {
		$this->workspaceDir = $workspaceDir;
	}

	/**
	 *
	 * @param string $filename
	 * @return array
	 */
	public function loadData( $filename ): array {
		$filepathname = $this->makeFilepathname( $filename );
		$data = [];
		if( file_exists( $filepathname ) ) {
			$data = require( $filepathname );
		}
		return is_array( $data ) ? $data : [];
	}

	/**
	 *
	 * @param string $filename
	 * @param array $data
	 * @return bool
	 */
	public function saveData( $filename, $data ): bool {
		$formattedData = var_export( $data, true );
		$fileContent = "<?php\n\nreturn $formattedData;";

		$filepathname = $this->makeFilepathname( $filename );
		$result = file_put_contents( $filepathname, $fileContent );

		return $result !== false;
	}

	/**
	 *
	 * @param string $filename
	 * @return string
	 */
	private function makeFilepathname( $filename ) {
		return $this->workspaceDir->getPathname() . "/$filename.php";
	}

	/**
	 *
	 * @param string $contentId
	 * @param string $rawData
	 *
	 * @return string The path
	 */
	public function saveRawContent( $contentId, $rawData ) {
		$this->ensurePath( 'content/raw' );
		$filepath = "/content/raw/$contentId.mraw";
		file_put_contents( $this->workspaceDir->getPathname().$filepath, $rawData );

		return $filepath;
	}

	private function ensurePath( $subpath ) {
		$path = $this->workspaceDir->getPathname() ."/$subpath";
		if( !file_exists( $path ) ) {
			mkdir($path, 0755, true );
		}
	}

	/**
	 *
	 * @param string $contentId
	 *
	 * @return string The wikitext content as created by step "convert"
	 */
	public function getConvertedContent( $contentId ) {
		$filepath = "/content/wikitext/$contentId.wiki";
		$content = file_get_contents( $this->workspaceDir->getPathname() . $filepath );

		return $content;
	}

	/**
	 *
	 * @param string $targetFileName
	 * @param string $content
	 * @return string The path
	 */
	public function saveUploadFile( $targetFileName, $content ) {
		$filepath = "/result/images/$targetFileName";
		$this->ensurePath( dirname( $filepath ) );
		file_put_contents( $this->workspaceDir->getPathname().$filepath, $content );

		return $filepath;
	}
}