<?php

namespace Barn2\Plugin\WC_Filters\Dependencies\Illuminate\Database\PDO;

use Barn2\Plugin\WC_Filters\Dependencies\Doctrine\DBAL\Driver\AbstractSQLiteDriver;
use Barn2\Plugin\WC_Filters\Dependencies\Illuminate\Database\PDO\Concerns\ConnectsToDatabase;
class SQLiteDriver extends AbstractSQLiteDriver
{
    use ConnectsToDatabase;
}
