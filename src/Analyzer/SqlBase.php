<?php

namespace HalloWelt\MediaWiki\Lib\Migration\Analyzer;

use HalloWelt\MediaWiki\Lib\Migration\AnalyzerBase;
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
abstract class SqlBase extends AnalyzerBase {

	/**
	 * @param SplFileInfo $file
	 * @return bool
	 * @throws \Exception
	 */
	protected function doAnalyze( SplFileInfo $file ): bool {
		if ( $file->getFilename() !== 'connection.json' ) {
			return true;
		}
		$connection = new SqlConnection( $file );
		foreach ( $connection->getTables() as $table ) {
			if ( !$this->analyzeTable( $connection, $table ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * @param SqlConnection $connection
	 * @param string $table
	 * @return bool
	 * @throws \Exception
	 */
	protected function analyzeTable( $connection, $table ) {
		$res = $connection->query(
			"SELECT * FROM $table"
		);
		if ( $res === null ) {
			throw new \Exception( "Cannot retrieve content of table \"$table\"" );
		}

		//phpcs:ignore Generic.CodeAnalysis.AssignmentInCondition.FoundInWhileCondition
		while ( $row = $res->fetch_assoc() ) {
			if ( !$this->analyzeRow( $row, $table ) ) {
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
	abstract protected function analyzeRow( $row, $table );
}
