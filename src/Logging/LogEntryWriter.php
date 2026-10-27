<?php

namespace HalloWelt\MediaWiki\Lib\Migration\Logging;

/**
 * Contract for a DB-backed log sink (e.g. `WorkspaceDB::addLogEntry`), matching a
 * `logging` table with columns `timestamp`, `type`, `step`, `caller`, `text`.
 */
interface LogEntryWriter {

	/**
	 * @param string $timestamp Log time in ISO 8601 format (`DateTimeInterface::ATOM`)
	 * @param string $type Log level name, e.g. "info", "error"
	 * @param string $step Migration step the message originated from (analyze/extract/convert/compose)
	 * @param string $caller Originating class/method, e.g. "Foo\\Bar::baz"
	 * @param string $text The log message
	 * @return void
	 */
	public function addLogEntry(
		string $timestamp,
		string $type,
		string $step,
		string $caller,
		string $text
	): void;
}
