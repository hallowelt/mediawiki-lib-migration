#General workflow

    bin/migrate.php analyze --src input/ --dest=workspace/
    bin/migrate.php extract --src input/ --dest=workspace/
    bin/migrate.php convert --src input/ --dest=workspace/
    bin/migrate.php compose --src input/ --dest=workspace/