<?php

namespace HalloWelt\MediaWiki\Lib\Migration;

use SplFileInfo;

class Workspace {

	/**
	 * @var protected
	 */
	private $workspaceDir = null;

	/**
	 * @param SplFileInfo $workspaceDir
	 */
	public function __construct( SplFileInfo $workspaceDir ) {
		$this->workspaceDir = $workspaceDir;
	}

	/**
	 * @param string $filename
	 * @param string $path
	 * @return array
	 */
	public function loadData( $filename, $path = '' ): array {
		$filepathname = $this->makeFilepathname( $filename );
		if ( $path !== '' ) {
			$filepathname = "$path/$filepathname";
		}
		$data = [];
		if ( file_exists( $filepathname ) ) {
			$data = require $filepathname;
		}
		return is_array( $data ) ? $data : [];
	}

	/**
	 * @param string $filename
	 * @param array $data
	 * @param string $path
	 * @return bool
	 */
	public function saveData( $filename, $data, $path = '' ): bool {
		$formattedData = var_export( $data, true );
		$fileContent = "<?php\n\nreturn $formattedData;";

		if ( $path !== '' ) {
			$this->ensurePath( $path );
			$filename = "$path/$filename";
		}

		$filepathname = $this->makeFilepathname( $filename );
		$result = file_put_contents( $filepathname, $fileContent );

		return $result !== false;
	}

	/**
	 * @param string $filename
	 * @return string
	 */
	private function makeFilepathname( $filename ) {
		return $this->workspaceDir->getPathname() . "/$filename.php";
	}

	/**
	 * @param string $contentId
	 * @param string $rawData
	 * @param string $path
	 *
	 * @return string The path
	 */
	public function saveRawContent( $contentId, $rawData, $path = 'content/raw' ) {
		$this->ensurePath( $path );
		$filepath = "/$path/$contentId.mraw";
		file_put_contents( $this->workspaceDir->getPathname() . $filepath, $rawData );

		return $filepath;
	}

	/**
	 * @param string $subpath
	 * @return void
	 */
	private function ensurePath( $subpath ) {
		$path = $this->workspaceDir->getPathname() . "/$subpath";
		if ( !file_exists( $path ) ) {
			mkdir( $path, 0755, true );
		}
	}

	/**
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
	 * @param string $targetFileName
	 * @param string $content
	 * @param string $path
	 * @return string The path
	 */
	public function saveUploadFile( $targetFileName, $content, $path = 'result/images' ) {
		$filepath = "/$path/$targetFileName";
		$this->ensurePath( dirname( $filepath ) );
		file_put_contents( $this->workspaceDir->getPathname() . $filepath, $content );

		return $filepath;
	}

	/**
	 * hard-link or copy a file from source to target
	 *
	 * If possible, use hardlinks. Test the hardlink possibility. A setup where hardlinks would
	 * fail is when source and target folders are mounted differently into a docker container.
	 *
	 * Disable hard linking when you plan to modify one but not the other file.
	 *
	 * @param string $sourceFile
	 * @param string $targetFile
	 * @param bool $allowLinking Whether to attempt creating a hard link before copying
	 * @return string The path to the target file after copying
	 */
	public function copyFile( string $sourceFile, string $targetFile, bool $allowLinking = true ): string {
		$this->ensurePath( dirname( $targetFile ) );
		$anchoredTargetFile = $this->workspaceDir->getPathname() . '/' . $targetFile;
		$canLink = false;
		if ( $allowLinking && function_exists( 'link' ) ) {
			// phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged
			$canLink = @link( $sourceFile, $anchoredTargetFile );
		}
		if ( !$canLink || !file_exists( $anchoredTargetFile ) ) {
			copy( $sourceFile, $anchoredTargetFile );
		}
		return $anchoredTargetFile;
	}
}
