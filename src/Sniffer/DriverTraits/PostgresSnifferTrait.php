<?php
declare(strict_types=1);

/**
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) 2020 Juan Pablo Ramirez and Nicolas Masson
 * @link          https://webrider.de/
 * @since         1.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */namespace CakephpTestSuiteLight\Sniffer\DriverTraits;


use Cake\Database\Connection;
use CakephpTestSuiteLight\Sniffer\TriggerBasedTableSnifferInterface;

trait PostgresSnifferTrait
{
    /**
     * @inheritDoc
     */
    public function getTriggers(): array
    {
        $triggerPrefix = TriggerBasedTableSnifferInterface::TRIGGER_PREFIX;
        $triggers = $this->fetchQuery("
            SELECT tgname
            FROM pg_trigger
            WHERE tgname LIKE '{$triggerPrefix}%'
        ");

        foreach ($triggers as $k => $trigger) {
            if (strpos($trigger, $triggerPrefix) !== 0) {
                unset($triggers[$k]);
            }
        }

        return (array)$triggers;
    }

    /**
     * @inheritDoc
     */
    public function dropTriggers(): void
    {
        $triggers = $this->getTriggers();
        if (empty($triggers)) {
            return;
        }

        foreach ($triggers as $trigger) {
            $table = substr($trigger, strlen(TriggerBasedTableSnifferInterface::TRIGGER_PREFIX));
            $this->getConnection()->execute("DROP TRIGGER {$trigger} ON {$table};");
        }
    }

    /**
     * @inheritDoc
     */
    public function fetchAllTables(): array
    {
        return $this->fetchQuery("            
            SELECT table_name
            FROM information_schema.tables
            WHERE table_schema = 'public'            
        ");
    }

    /**
     * @inheritDoc
     */
    public function dropTables(array $tables): void
    {
        if (empty($tables)) {
            return;
        }

        $this->getConnection()->disableConstraints(function (Connection $connection) use ($tables) {
            $tables[] = TriggerBasedTableSnifferInterface::DIRTY_TABLE_COLLECTOR;
            foreach ($tables as $table) {
                $connection->execute(
                    'DROP TABLE IF EXISTS "' . $table  . '" CASCADE;'
                );
            }
        });
    }
}