# General workflow

    bin/migrate.php analyze --src input/ --dest=workspace/
    bin/migrate.php extract --src input/ --dest=workspace/
    bin/migrate.php convert --src workspace/ --dest=workspace/
    bin/migrate.php compose --src workspace/ --dest=workspace/

# TODO
 - Unit tests for
  - `DataBuckets`
  - `WindowsFilename`
  - `Workspace`
