<?php

namespace HalloWelt\MediaWiki\Lib\Migration;

use SplFileInfo;

class SqlConnection {
	/** @var array */
	private $tables;
	/** @var \mysqli */
	private $connection;

	/**
	 * @param SplFileInfo $file
	 */
	public function __construct( $file ) {
		$connectionConfig = $this->getConnectionConfigFromFile( $file );
		if ( $connectionConfig === null ) {
			throw new \InvalidArgumentException( "Connection config file must be a valid JSON" );
		}
		$this->connection = $this->connect( $connectionConfig );
		if ( !$this->connection ) {
			throw new \InvalidArgumentException( "Invalid database connection config" );
		}
		$this->tables = $connectionConfig['tables'] ?? [];
	}

	/**
	 * @return array
	 */
	public function getTables() {
		return $this->tables;
	}

	/**
	 * @param string $query
	 * @return bool|\mysqli_result
	 */
	public function query( $query ) {
		return $this->connection->query( $query );
	}

	/**
	 * @param array $config
	 * @return false|\mysqli
	 */
	private function connect( $config ) {
		if ( !isset( $config['database'] ) ) {
			throw new \InvalidArgumentException( 'Param \"database\" must be provided' );
		}
		return mysqli_connect(
			$config['hostname'] ?? ini_get( "mysqli.default_host" ),
			$config['username'] ?? ini_get( "mysqli.default_user" ),
			$config['password'] ?? ini_get( "mysqli.default_pw" ),
			$config['database'],
			isset( $config['port'] ) ? (int)$config['port'] : ini_get( "mysqli.default_port" ),
			$config['socket'] ?? ini_get( "mysqli.default_socket" )
		);
	}

	/**
	 * @param SplFileInfo $fileInfo
	 * @return array|null
	 */
	private function getConnectionConfigFromFile( SplFileInfo $fileInfo ) {
		$json = file_get_contents( $fileInfo->getRealPath() );
		$processed = json_decode( $json, true );
		if ( !is_array( $processed ) ) {
			return null;
		}
		return $processed;
	}
}
