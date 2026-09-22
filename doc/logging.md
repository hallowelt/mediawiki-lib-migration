# Logging

The library provides a single, static logging facade,
`HalloWelt\MediaWiki\Lib\Migration\Logging\Log`, built on top of
[Monolog](https://github.com/Seldaek/monolog). It is designed so that
**no dependency injection is required**: any class in this library or in a
downstream project can call `Log::info( ... )`, `Log::error( ... )` etc.
directly, without a constructor parameter or a `getLogger()` call.

A single log message can be routed to any combination of three targets at
the same time, each with its own independent verbosity:

- **console** (STDOUT/STDERR), via Symfony Console's `ConsoleLogger`
- **file**, via Monolog's `StreamHandler`
- **database**, via a `LogEntryWriter` implementation (e.g. `WorkspaceDB`)

## Quick start

Without any configuration, `Log::*()` calls already work out of the box:
messages of level `notice` and above are printed to the console (colored iff
the output is a TTY). File and database logging are opt-in.

```php
use HalloWelt\MediaWiki\Lib\Migration\Logging\Log;

Log::info( 'Processing {file}', [ 'file' => $path ] ); // PSR-3 {} interpolation
Log::warning( 'Unexpected markup encountered' );
Log::error( 'Could not parse title' );
```

## Configuration

Call `Log::configure()` **once**, early in your downstream project's
bootstrap (before any `Log::*()` call you care about), typically after
reading your own YAML/JSON config file. The configuration format is
inspired by
[Django's `LOGGING` setting](https://docs.djangoproject.com/en/6.0/ref/logging/#default-logging-configuration):
a dict of named `formatters` and a dict of named `handlers`. Every handler
receives every log record; each handler's own `level` decides whether it
actually reports the message ("independent verbosity per channel").

```php
use HalloWelt\MediaWiki\Lib\Migration\Logging\Log;

Log::configure( [
    'formatters' => [
        'file' => [
            'format' => "%datetime% %channel%.%level_name%: %message% %context%\n",
        ],
    ],
    'handlers' => [
        'console' => [ 'level' => 'notice' ],
        'file' => [
            'level' => 'debug',
            'path' => '/var/log/migration/run.log',
            'formatter' => 'file',
        ],
    ],
] );
```

A handler entry may also provide a ready-made `HandlerInterface` instance
directly via the `handler` key, for target types beyond the built-in
`console`/file (`path`) ones.

Re-calling `Log::configure()` replaces all previously configured handlers
except the database handler (see below), which is expected to be set up
once, independently, and to survive reconfiguration.

### Database handler

The database target is not part of `Log::configure()`'s `handlers` array,
because it needs a concrete `LogEntryWriter` instance (e.g. your project's
`WorkspaceDB`, opened against a specific workspace/SQLite file) rather than
a static config array. Wire it up separately, as early as that instance is
available:

```php
use HalloWelt\MediaWiki\Lib\Migration\Logging\Log;

// $writer implements LogEntryWriter::addLogEntry( $timestamp, $type, $step, $caller, $text )
Log::setDatabaseHandler( $writer, 'info' ); // e.g. skip debug noise in the DB
```

The expected table shape (columns `timestamp`, `type`, `step`, `caller`,
`text`) mirrors the `LogEntryWriter` interface; a different `LogEntryWriter`
implementation may use a different schema as long as it exposes the same
five values per call. `$timestamp` is the log record's time formatted as
ISO 8601 (`DateTimeInterface::ATOM`, e.g. `2026-09-22T14:03:00+02:00`).

### Console handler and `--quiet`/`--silent`

If your project subclasses `CliCommandBase` (as `Analyze`/`Extract`/
`Convert`/`Compose` do), the console handler is automatically routed
through the command's real Symfony `Output` object
(`Log::setConsoleOutput()`, called from `beforeProcessFiles()`), so
`Log::*()` shares the exact same stream, color/TTY detection, and verbosity
flags (`-v`, `-q`, ...) as code still writing directly via
`$output->writeln()`.

The current migration step (`analyze`/`extract`/`convert`/`compose`) is
likewise tagged automatically via `Log::setStep()`, using the command's
name; this value ends up in the database handler's `step` column.

Console verbosity deliberately departs from `ConsoleLogger`'s own defaults
in two ways:

- `notice` is always shown at normal verbosity (like `warning`/`error`),
  not gated behind `-v`. This preserves the visibility that plain
  `$output->writeln()` calls used to have before being migrated to `Log::`.
- `error`, `critical`, `alert` and `emergency` are shown even when the
  command is run with `--quiet`. A migration failure should not be
  silenced by the same flag that quiets down progress/informational
  noise. Use `--silent` (where supported by the console application) to
  suppress console output entirely, including errors.

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
| `notice` | Noteworthy but non-error events (default console visibility) |
| `warning` | Something unexpected happened but processing continues |
| `error` | An operation failed, but the overall run can continue |
| `critical` | A whole step/component is broken and cannot continue |
| `alert`, `emergency` | Rarely used; total failure requiring immediate attention |

### Console visibility per level (default configuration)

| Level | Normal | `--quiet` | `--silent` | `-v` | `-vv` | `-vvv` |
|---|---|---|---|---|---|---|
| `debug` | - | - | - | - | - | shown |
| `info` | - | - | - | - | shown | shown |
| `notice` | shown | shown | - | shown | shown | shown |
| `warning` | shown | - | - | shown | shown | shown |
| `error`/`critical`/`alert`/`emergency` | shown | shown | - | shown | shown | shown |

File and database handlers are unaffected by console verbosity flags; they
only respect their own configured `level` from `Log::configure()`/
`Log::setDatabaseHandler()`.

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
Names are the keys used in `configure()`'s `handlers` array, plus the
built-in `console` and `db`:

```php
Log::to( 'file' )->debug( 'verbose parsing detail, keep it out of the console' );
Log::to( 'db', 'console' )->warning( 'user-visible + audited, but not in the log file' );
```
