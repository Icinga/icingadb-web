<?php

/* Icinga DB Web | (c) 2025 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\ProvidedHook\Notifications\V1;

use Icinga\Application\Logger;
use Icinga\Exception\ConfigurationError;
use Icinga\Module\Icingadb\Common\Backend;
use Icinga\Module\Icingadb\Model\Host;
use Icinga\Module\Icingadb\Model\Service;
use Icinga\Module\Notifications\Hook\V1\SourceHook;
use InvalidArgumentException;
use ipl\Html\Attributes;
use ipl\Html\Contract\Form;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\I18n\Translation;
use ipl\Sql\Expression;
use ipl\Stdlib\Filter;
use ipl\Stdlib\Filter\Condition;
use ipl\Web\Control\SearchBar\SearchException;
use ipl\Web\Control\SearchEditor;
use ipl\Web\Filter\QueryString;
use ipl\Web\Filter\Renderer;
use ipl\Web\Url;
use ipl\Web\Widget\IcingaIcon;
use ipl\Web\Widget\Icon;
use JsonException;

class Source implements SourceHook
{
    use Translation;

    /** @var string */
    public const TYPE_ALL = 'all';

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

    public function getRuleFilterTargets(int $sourceId): array
    {
        return [
            'host' => $this->translate('Hosts only'),
            'service' => $this->translate('Services only'),
            self::TYPE_ALL => $this->translate('Hosts and Services')
        ];
    }

    public function getRuleFilterEditor(string $filter): SearchEditor
    {
        if ($filter === 'host' || $filter === 'service' || $filter === self::TYPE_ALL) {
            $type = $filter;
            $filter = '';
        } else {
            try {
                $data = json_decode($filter, true, flags: JSON_THROW_ON_ERROR);
            } catch (JsonException $e) {
                Logger::error('Failed to parse rule filter configuration: %s (Error: %s)', $filter, $e);
                throw new ConfigurationError($this->translate(
                    'Failed to parse rule filter configuration. Please contact your system administrator.'
                ));
            }

            if ($data['version'] !== 1 || ! isset($data['config']['type']) || ! isset($data['config']['filter'])) {
                Logger::error('Invalid rule filter configuration: %s', $filter);
                throw new ConfigurationError($this->translate(
                    'Invalid rule filter configuration. Please contact your system administrator.'
                ));
            }

            $type = $data['config']['type'];
            $filter = $data['config']['filter'];
        }

        $editor = new SearchEditor();
        $editor->setQueryString($filter);
        $editor->setSuggestionUrl(Url::fromPath(
            'icingadb/suggest/restriction-column',
            ['_disableLayout' => true, 'showCompact' => true, 'type' => $type]
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
            if (! array_key_exists($condition->getColumn(), $this->allowedColumns)) {
                if (! preg_match('/^(?:host|service)\.vars\./i', $condition->getColumn())) {
                    throw new SearchException($this->translate('Is not a valid column'));
                }
            }
        })->on(HtmlDocument::ON_ASSEMBLED, function (SearchEditor $editor) use ($type) {
            $editor->prependHtml(new HtmlElement(
                'p',
                Attributes::create(['class' => 'description']),
                Text::create(
                    match ($type) {
                        'host' => $this->translate(
                            'Only hosts matching the following criteria will be affected.'
                        ),
                        'service' => $this->translate(
                            'Only services matching the following criteria will be affected.'
                        ),
                        self::TYPE_ALL => $this->translate(
                            'All hosts and services matching the following criteria will be affected.'
                        )
                    }
                )
            ));

            // Not using addElement, as otherwise the submit button is hidden because it's not last-of-type
            $hidden = $editor->createElement('hidden', 'type', ['value' => $type]);
            $editor->registerElement($hidden);
            $editor->prependHtml($hidden);
        });

        return $editor;
    }

    public function serializeRuleFilter(Form $editor): string
    {
        if (! $editor instanceof SearchEditor) {
            throw new InvalidArgumentException('Editor must be an instance of ' . SearchEditor::class);
        }

        $rule = $editor->getFilter();
        $filter = (new Renderer($rule))->render();
        if ($filter === '') {
            return '';
        }

        $type = $editor->getElement('type')->getValue();

        $queries = [];
        if ($type === 'host' || $type === self::TYPE_ALL) {
            $queries['host'] = Host::on(Backend::getDb())
                ->filter(Filter::all(
                    Filter::equal('host.id', ':host_id'),
                    Filter::equal('host.environment_id', ':environment_id')
                ));
        }

        if ($type === 'service' || $type === self::TYPE_ALL) {
            $queries['service'] = Service::on(Backend::getDb())
                ->filter(Filter::all(
                    Filter::equal('service.id', ':service_id'),
                    Filter::equal('service.environment_id', ':environment_id')
                ));
        }

        return json_encode([
            'version' => 1,
            'config' => [
                'type' => $type,
                'filter' => $filter
            ],
            'queries' => array_map(function ($query) use ($rule) {
                $query->columns([new Expression('1')])->filter($rule)->limit(1);

                [$query, $parameters] = $query->getDb()->getQueryBuilder()->assembleSelect(
                    $query->assembleSelect()->resetOrderBy()
                );

                return [
                    'query' => $query,
                    'parameters' => $parameters
                ];
            }, $queries)
        ], JSON_THROW_ON_ERROR);
    }
}
