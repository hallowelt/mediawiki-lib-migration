<?php

namespace HalloWelt\MediaWiki\Lib\Migration\Logging;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;
use Monolog\LogRecord;
use Throwable;

/**
 * Writes log records to a `LogEntryWriter` (e.g. `WorkspaceDB::addLogEntry`).
 */
class DatabaseHandler extends AbstractProcessingHandler {

	/** @var LogEntryWriter */
	private $writer;

	/**
	 * @param LogEntryWriter $writer
	 * @param int|string|Level $level
	 * @param bool $bubble
	 */
	public function __construct( LogEntryWriter $writer, $level = Level::Debug, bool $bubble = true ) {
		parent::__construct( $level, $bubble );
		$this->writer = $writer;
	}

	/**
	 * @param LogRecord $record
	 * @return void
	 */
	protected function write( LogRecord $record ): void {
		$text = $record->message;
		if ( ( $record->context['exception'] ?? null ) instanceof Throwable ) {
			$text .= "\n" . (string)$record->context['exception'];
		}
		$this->writer->addLogEntry(
			$record->datetime->format( DATE_ATOM ),
			$record->level->getName(),
			(string)( $record->context['step'] ?? '' ),
			(string)( $record->context['caller'] ?? $this->formatCaller( $record->extra ) ),
			$text
		);
	}

	/**
	 * @param array $extra
	 * @return string
	 */
	private function formatCaller( array $extra ): string {
		if ( isset( $extra['class'] ) && isset( $extra['function'] ) ) {
			return $extra['class'] . ( $extra['function'] ? '::' . $extra['function'] : '' );
		}
		return (string)( $extra['file'] ?? '' ) . ( isset( $extra['line'] ) ? ':' . $extra['line'] : '' );
	}
}
