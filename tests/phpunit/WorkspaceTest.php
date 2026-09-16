<?php

namespace HalloWelt\MediaWiki\Lib\Migration\Tests;

use HalloWelt\MediaWiki\Lib\Migration\Workspace;
use PHPUnit\Framework\TestCase;
use SplFileInfo;

class WorkspaceTest extends TestCase {

	/** @var string */
	private $tmpDir;

	/** @var string */
	private $sourceFile;

	protected function setUp(): void {
		parent::setUp();
		$this->tmpDir = sys_get_temp_dir() . '/WorkspaceTest_' . uniqid();
		mkdir( $this->tmpDir, 0755, true );
		$this->sourceFile = $this->tmpDir . '/source.txt';
		file_put_contents( $this->sourceFile, 'content' );
	}

	protected function tearDown(): void {
		foreach ( glob( $this->tmpDir . '/workspace/*' ) ?: [] as $file ) {
			unlink( $file );
		}
		if ( is_dir( $this->tmpDir . '/workspace' ) ) {
			rmdir( $this->tmpDir . '/workspace' );
		}
		unlink( $this->sourceFile );
		rmdir( $this->tmpDir );
		parent::tearDown();
	}

	/**
	 * @covers HalloWelt\MediaWiki\Lib\Migration\Workspace::copyFile
	 * @return void
	 */
	public function testCopyFileHardLinksWhenAllowed() {
		$workspace = new Workspace( new SplFileInfo( $this->tmpDir . '/workspace' ) );
		$targetFile = $workspace->copyFile( $this->sourceFile, 'target.txt', true );

		$this->assertFileExists( $targetFile );
		$this->assertSame(
			fileinode( $this->sourceFile ),
			fileinode( $targetFile ),
			'Target file should be a hard link to the source file (same inode)'
		);
	}

	/**
	 * @covers HalloWelt\MediaWiki\Lib\Migration\Workspace::copyFile
	 * @return void
	 */
	public function testCopyFileCopiesOnlyWhenLinkingDisallowed() {
		$workspace = new Workspace( new SplFileInfo( $this->tmpDir . '/workspace' ) );
		$targetFile = $workspace->copyFile( $this->sourceFile, 'target.txt', false );

		$this->assertFileExists( $targetFile );
		$this->assertNotSame(
			fileinode( $this->sourceFile ),
			fileinode( $targetFile ),
			'Target file should be an independent copy, not a hard link'
		);
		$this->assertSame( file_get_contents( $this->sourceFile ), file_get_contents( $targetFile ) );
	}
}
