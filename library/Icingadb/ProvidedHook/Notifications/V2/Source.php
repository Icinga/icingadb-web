<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Icingadb\ProvidedHook\Notifications\V2;

use Icinga\Module\Notifications\Hook\V2\SourceHook;
use ipl\I18n\Translation;
use ipl\Stdlib\Filter\Condition;
use ipl\Web\Control\SearchBar\SearchException;
use ipl\Web\Control\SearchEditor;
use ipl\Web\Filter\QueryString;
use ipl\Web\Url;
use ipl\Web\Widget\IcingaIcon;
use ipl\Web\Widget\Icon;

class Source implements SourceHook
{
    use Translation;

    /** @var array<string, string> */
    private array $allowedColumns;

    public function __construct()
    {
        $this->allowedColumns = [
            'host.name' => $this->translate('Host Name'),
            'hostgroup.name' => $this->translate('Hostgroup Name'),
            'host.user.name' => $this->translate('Contact Name'),
            'host.usergroup.name' => $this->translate('Contactgroup Name'),
            'service.name' => $this->translate('Service Name'),
            'servicegroup.name' => $this->translate('Servicegroup Name'),
            'service.user.name' => $this->translate('Contact Name'),
            'service.usergroup.name' => $this->translate('Contactgroup Name')
        ];
    }

    public function getSourceType(): string
    {
        return 'icinga2';
    }

    public function getSourceLabel(): string
    {
        return 'Icinga';
    }

    public function getSourceIcon(): Icon
    {
        return new IcingaIcon('icinga');
    }

    public function getRuleFilterEditor(string $filter): SearchEditor
    {
        $editor = (new SearchEditor())
            ->setQueryString($filter)
            ->setSuggestionUrl(Url::fromPath(
                'icingadb/suggest/restriction-column',
                ['_disableLayout' => true, 'showCompact' => true]
            ));
        $editor->getParser()->on(QueryString::ON_CONDITION, function (Condition $condition) {
            if ($condition->getColumn()) {
                if (array_key_exists($condition->getColumn(), $this->allowedColumns)) {
                    $condition->metaData()->set('columnLabel', $this->allowedColumns[$condition->getColumn()]);
                } elseif (preg_match('/^(host|service)\.vars\.(.*)/i', $condition->getColumn(), $m)) {
                    $prefix = $m[1] === 'host' ? $this->translate('Host') : $this->translate('Service');
                    $condition->metaData()->set('columnLabel', $prefix . ' ' . $m[2]);
                }
            }
        });
        $editor->on(SearchEditor::ON_VALIDATE_COLUMN, function (Condition $condition) {
            if (
                ! array_key_exists($condition->getColumn(), $this->allowedColumns)
                && ! preg_match('/^(?:host|service)\.vars\./i', $condition->getColumn())
            ) {
                throw new SearchException($this->translate('Is not a valid column'));
            }
        });

        return $editor;
    }

    public function getSuggestionUrl(): Url
    {
        return Url::fromPath(
            'icingadb/suggest/restriction-column',
            ['_disableLayout' => true, 'showCompact' => true]
        );
    }

    public function isValidColumn(string $column): bool
    {
        return array_key_exists($column, $this->allowedColumns)
            || preg_match('/^(?:host|service)\.vars\./i', $column);
    }

    public function getColumnLabel(string $column): ?string
    {
        return $this->allowedColumns[$column] ?? null;
    }
}
