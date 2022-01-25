<?php

namespace HalloWelt\MediaWiki\Lib\Migration\Extractor;

use HalloWelt\MediaWiki\Lib\Migration\ExtractorBase;
use HalloWelt\MediaWiki\Lib\Migration\SqlConnection;
use SplFileInfo;

/**
 * Expected input file content of input file "connection.json"
 * {
 * 	"hostname": "db_host",
 * 	"username": "db_user",
 *	"password": "db_pass",
 *	"database": "db_name",
 *	"tables": [ "list", "of", "tables" ]
 * }
 */
abstract class SqlBase extends ExtractorBase {

	/**
	 * @param SplFileInfo $file
	 * @return bool
	 * @throws \Exception
	 */
	protected function doExtract( SplFileInfo $file ): bool {
		if ( $file->getFilename() !== 'connection.json' ) {
			return true;
		}
		$connection = new SqlConnection( $file );
		foreach ( $connection->getTables() as $table ) {
			if ( !$this->extractFromTable( $connection, $table ) ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * @param \mysqli $connection
	 * @param string $table
	 * @return bool
	 * @throws \Exception
	 */
	protected function extractFromTable( $connection, $table ) {
		$res = $connection->query(
			"SELECT * FROM $table"
		);
		if ( $res === null ) {
			throw new \Exception( "Cannot retrieve content of table \"$table\"" );
		}

		//phpcs:ignore MediaWiki.ControlStructures.AssignmentInControlStructures.AssignmentInControlStructures
		while ( $row = $res->fetch_assoc() ) {
			if ( !$this->extractRow( $row, $table ) ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * @param array|null $row
	 * @param string $table
	 * @return bool
	 */
	abstract protected function extractRow( $row, $table );
}
