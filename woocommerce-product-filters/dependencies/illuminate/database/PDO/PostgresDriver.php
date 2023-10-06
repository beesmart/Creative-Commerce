<?php

namespace Barn2\Plugin\WC_Filters\Dependencies\Illuminate\Database\PDO;

use Barn2\Plugin\WC_Filters\Dependencies\Doctrine\DBAL\Driver\AbstractPostgreSQLDriver;
use Barn2\Plugin\WC_Filters\Dependencies\Illuminate\Database\PDO\Concerns\ConnectsToDatabase;
class PostgresDriver extends AbstractPostgreSQLDriver
{
    use ConnectsToDatabase;
}
