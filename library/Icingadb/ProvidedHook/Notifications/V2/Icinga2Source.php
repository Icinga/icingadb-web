<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Icingadb\ProvidedHook\Notifications\V2;

use Icinga\Module\Icingadb\Common\Auth;
use Icinga\Module\Icingadb\Common\Database;
use Icinga\Module\Icingadb\Data\QueryColumnsProvider;
use Icinga\Module\Icingadb\Data\QueryValuesProvider;
use Icinga\Module\Icingadb\Model\Customvar;
use Icinga\Module\Icingadb\Model\CustomvarFlat;
use Icinga\Module\Icingadb\Model\Host;
use Icinga\Module\Notifications\Hook\V2\SourceHook;
use ipl\I18n\Translation;
use ipl\Stdlib\Filter;
use ipl\Stdlib\Filter\Chain;
use ipl\Stdlib\Filter\Condition;
use ipl\Web\Control\SearchBar\SearchException;
use ipl\Web\Widget\IcingaIcon;
use ipl\Web\Widget\Icon;
use Traversable;

class Icinga2Source implements SourceHook
{
    use Auth;
    use Database;
    use Translation;

    /** @var array<string, string> The allowed search columns */
    private array $allowedColumns;

    /** @var array<string, array<string, array<string, string>>> The selected custom vars */
    private array $selectedCustomVars = [];

    public function __construct()
    {
        $this->allowedColumns = [
            'host.name' => $this->translate('Host Name'),
            'hostgroup.name' => $this->translate('Hostgroup Name'),
            'service.name' => $this->translate('Service Name'),
            'servicegroup.name' => $this->translate('Servicegroup Name')
        ];
    }

    public function getSourceLabel(): string
    {
        return 'Icinga';
    }

    public function getSourceIcon(): Icon
    {
        return new IcingaIcon('icinga');
    }

    public function assertValidCondition(Condition $condition): void
    {
        $column = $condition->getColumn();
        if (isset($this->allowedColumns[$column])) {
            return;
        }

        if (preg_match('/^(host|service)\.vars\.(.*?)(\[\*])?$/i', $column, $m)) {
            // $m[3] captures the trailing [*], so $m[2] should never contain a wildcard.
            if (str_contains($m[2], '*')) {
                throw new SearchException($this->translate('Wildcards are not allowed in custom variables'));
            }

            [$objectType, $customvar] = [$m[1], $m[2] . ($m[3] ?? '')];
            $vars = Customvar::on($this->getDb())
                ->columns(['name', 'value', 'path' => 'customvar_flat.flatname'])
                ->filter(Filter::all(
                    Filter::like(sprintf('%s.id', $objectType), '*'),
                    Filter::like('customvar_flat.flatname', $customvar)
                ));

            $this->applyRestrictions($vars);

            $found = false;
            foreach ($vars as $var) {
                $found = true;
                $this->selectedCustomVars[$column][$var->name][$var->value] = $var->path;
            }

            if (! $found) {
                throw new SearchException(
                    $this->translate('Custom variable not found. Please define it before referencing it in a rule')
                );
            }
        } else {
            throw new SearchException($this->translate('Is not a valid Column'));
        }
    }

    public function enrichCondition(Condition $condition): void
    {
        $column = $condition->getColumn();
        if (isset($this->allowedColumns[$column])) {
            $condition->metaData()->set('columnLabel', $this->allowedColumns[$column]);
        } elseif (preg_match('/^(host|service)\.vars\.(.*?)(\[\*])?$/i', $column, $m)) {
            $prefix = $m[1] === 'host' ? $this->translate('Host') : $this->translate('Service');
            // $m[3] captures the trailing [*] and is ignored
            $condition->metaData()->set('columnLabel', $prefix . ' ' . $m[2]);
        }
    }

    public function getValueSuggestions(string $column, string $searchTerm, Chain $searchFilter): Traversable
    {
        return new QueryValuesProvider(
            Host::on($this->getDb())->limit(50),
            $column,
            $searchTerm,
            $searchFilter
        );
    }

    public function getColumnSuggestions(string $searchTerm): Traversable
    {
        return (new QueryColumnsProvider(Host::on($this->getDb()), $searchTerm))
            ->setCustomVarSources([
                'host' => $this->translate('Host %s', '..<customvar-name>'),
                'service' => $this->translate('Service %s', '..<customvar-name>')
            ])
            ->setFixedColumns($this->allowedColumns)
            ->setShowRelationLabels();
    }

    public function getJsonPaths(string ...$columns): array
    {
        $simulateCustomvarFlatResults = function ($column) {
            foreach ($this->selectedCustomVars[$column] as $varName => $vars) {
                foreach ($vars as $value => $flatname) {
                    yield new CustomvarFlat([
                        'flatname' => $flatname,
                        'flatvalue' => 'true',
                        'customvar' => new Customvar([
                            'name' => $varName,
                            'value' => $value
                        ])
                    ]);
                }
            }
        };

        $paths = [];
        foreach ($columns as $column) {
            if (isset($this->allowedColumns[$column])) {
                $paths[$column][] = $this->replacePrefix($column);
            } else {
                $prefix = str_starts_with($column, 'host.vars') ? 'host.vars' : 'services[*].vars';
                $unflattenVars = (new CustomvarFlat())->unFlattenVars($simulateCustomvarFlatResults($column));
                $paths[$column] = $this->createJsonPaths($prefix, $unflattenVars);
            }
        }

        return $paths;
    }

    /**
     * Create JSON paths for the given custom variable tree
     *
     * @param string $key
     * @param mixed $value
     * @param string[] $segments
     * @param string[] $paths
     *
     * @return string[] Flat array containing JSON paths
     */
    private function createJsonPaths(string $key, mixed $value, array $segments = [], array &$paths = []): array
    {
        if (is_array($value)) {
            $segments[] = $key;
            foreach ($value as $k => $v) {
                $this->createJsonPaths(
                    is_int($k)
                        ? sprintf('[%d]', $k)
                        : sprintf("['%s']", addslashes($k)),
                    $v,
                    $segments,
                    $paths
                );
            }
        } else {
            if (preg_match('/^\[\d+]$/', $key)) {
                $segments[] = '[*]';
            } else {
                $segments[] = $key;
            }

            $paths[implode('', $segments)] = true;
        }

        return array_keys($paths);
    }

    /**
     * Replace prefix based on a predefined map
     *
     * @param string $column
     *
     * @return string
     */
    private function replacePrefix(string $column): string
    {
        $replacements = [
            'service.'      => 'services[*].',
            'hostgroup.'    => 'hostgroups[*].',
            'servicegroup.' => 'servicegroups[*].'
        ];

        $prefix = strstr($column, '.', true) . '.';

        if (! isset($replacements[$prefix])) {
            return $column;
        }

        foreach ($replacements as $search => $replace) {
            if (str_starts_with($column, $search)) {
                return $replace . substr($column, strlen($search));
            }
        }

        return $column;
    }
}
