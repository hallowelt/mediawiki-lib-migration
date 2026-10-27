# Logging

The library provides a single, static logging facade,
`HalloWelt\MediaWiki\Lib\Migration\Logging\Log`, built on top of
[Monolog](https://github.com/Seldaek/monolog). It is designed so that
**no dependency injection is required**: any class in this library or in a
downstream project can call `Log::info( ... )`, `Log::error( ... )` etc.
directly, without a constructor parameter or a `getLogger()` call.

A single log message can be routed to any combination of two targets at
the same time, each with its own independent verbosity:

- **console** (STDOUT/STDERR), via Symfony Console's `ConsoleLogger`
- **database**, via a `LogEntryWriter` implementation (e.g. `WorkspaceDB`)

## Quick start

Without any configuration, `Log::*()` calls already work out of the box:
messages of level `warning` and above are printed to the console (colored iff
the output is a TTY); `notice`, `info` and `debug` appear with `-v`, `-vv` and
`-vvv`, see
[Console visibility per level](#console-visibility-per-level-default-configuration).
Database logging is opt-in, see [Database handler](#database-handler) below.

```php
use HalloWelt\MediaWiki\Lib\Migration\Logging\Log;

Log::info( 'Processing {file}', [ 'file' => $path ] ); // PSR-3 {} interpolation
Log::warning( 'Unexpected markup encountered' );
Log::error( 'Could not parse title' );
```

### Database handler

`Log::setDatabaseHandler()` requires a concrete `LogEntryWriter` instance
(e.g. your project's `WorkspaceDB`, opened against a specific
workspace/SQLite file). Wire it up early, as soon as that instance is
available:

```php
use HalloWelt\MediaWiki\Lib\Migration\Logging\Log;

// $writer implements LogEntryWriter::addLogEntry( $timestamp, $type, $step, $caller, $text )
Log::setDatabaseHandler( $writer, 'info' ); // e.g. skip debug noise in the DB
```

Calling `setDatabaseHandler()` again replaces the previous DB handler;
`Log::setDatabaseHandler( null )` removes it (useful in tests and in worker
processes that need a different sink than the parent). `Log::to( 'db' )`
targets only this handler.

The expected table shape (columns `timestamp`, `type`, `step`, `caller`,
`text`) mirrors the `LogEntryWriter` interface; a different `LogEntryWriter`
implementation may use a different schema as long as it exposes the same
five values per call. `$timestamp` is the log record's time formatted as
ISO 8601 (`DateTimeInterface::ATOM`, e.g. `2026-09-22T14:03:00+02:00`).

### Console handler and verbosity

`CliApp` routes the console handler through the running command's real
Symfony `Output` object for **every** command, including downstream
commands that do not extend `CliCommandBase` (it overrides
`doRunCommand()` and calls `Log::setConsoleOutput()`). So `Log::*()` shares
the exact same stream, color/TTY detection, and verbosity flags (`-v`,
`-q`, ...) as code still writing directly via `$output->writeln()`.
`CliCommandBase::beforeProcessFiles()` does the same for its subclasses.

`CliApp` also hands `Log::getLogger()` (the shared PSR-3 logger) to every
command that implements `Psr\Log\LoggerAwareInterface`. The base command
`BatchFileProcessorBase` (hallowelt/mediawiki-lib-commandline-tools) does,
and reports its progress (`Source`, `Destination`, file list, `Done.`) and
errors (e.g. `Failed to process file`) at `info` and `error` level through
it, so they respect the verbosity flags and reach the database handler.

The console handler's own level defaults to `debug`, so what is shown is
decided solely by the verbosity flags. Only call `Log::setConsoleOutput()`
yourself if you run commands outside `CliApp`.

The current migration step (`analyze`/`extract`/`convert`/`compose`) is
likewise tagged automatically via `Log::setStep()`, using the command's
name; this value ends up in the database handler's `step` column.

Console verbosity follows Symfony's `ConsoleLogger` defaults, without any
special casing: see the table below. `--quiet` hides all log output; use
`--silent` (where supported by the console application) likewise.

## Log levels

Standard [PSR-3](https://www.php-fig.org/psr/psr-3/) levels are available
as static methods, from least to most severe:
`debug`, `info`, `notice`, `warning`, `error`, `critical`, `alert`,
`emergency`. Pick a level based on what it should mean for *all* configured
targets at once, since a single call reaches every handler:

| Level | Typical use |
|---|---|
| `debug` | Fine-grained diagnostic detail (e.g. "scanning file X") |
| `info` | Normal, expected progress information |
| `notice` | Noteworthy but non-error events (shown with `-v`) |
| `warning` | Something unexpected happened but processing continues |
| `error` | An operation failed, but the overall run can continue |
| `critical` | A whole step/component is broken and cannot continue |
| `alert`, `emergency` | Rarely used; total failure requiring immediate attention |

### Console visibility per level (default configuration)

| Level | Normal | `--quiet` | `--silent` | `-v` | `-vv` | `-vvv` |
|---|---|---|---|---|---|---|
| `debug` | - | - | - | - | - | shown |
| `info` | - | - | - | - | shown | shown |
| `notice` | - | - | - | shown | shown | shown |
| `warning` | shown | - | - | shown | shown | shown |
| `error`/`critical`/`alert`/`emergency` | shown | - | - | shown | shown | shown |

The database handler is unaffected by console verbosity flags; it only
respects its own configured `level` from `Log::setDatabaseHandler()`.

## PSR-3 message interpolation

Messages support `{placeholder}` interpolation from the context array:

```php
Log::info( 'Processing {file} for user {user}', [ 'file' => $path, 'user' => $user ] );
```

## Logging exceptions

Use `Log::logException()` to log a `Throwable` with its message and full
stack trace reaching every configured target (level defaults to `error`):

```php
try {
    $this->doSomethingRisky();
} catch ( \Throwable $e ) {
    Log::logException( $e );                              // level=error
    Log::logException( $e, [ 'file' => $path ], 'critical' ); // extra context + custom level
}
```

## Addressing specific handlers

`Log::to( ...$names )` returns a scoped logger that only reaches the named
handlers, for the rare cases where a message should not go everywhere.
The only built-in names are `console` and `db` (see
[Database handler](#database-handler)); there is no public API to register
further named handlers.

```php
Log::to( 'db' )->debug( 'audit detail, keep it out of the console' );
Log::to( 'db', 'console' )->warning( 'user-visible + audited' );
```
