<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Common;

use DateTime;
use DateTimeZone;
use Exception;
use Icinga\Exception\Json\JsonDecodeException;
use Icinga\Module\Icingadb\Model\Host;
use Icinga\Module\Icingadb\Model\Service;
use Icinga\Module\Icingadb\Widget\EmptyState;
use Icinga\Util\Format;
use Icinga\Util\Json;
use ipl\Html\BaseHtmlElement;
use ipl\Html\FormattedString;
use ipl\Html\HtmlElement;
use ipl\Html\Table;
use ipl\Html\Text;
use ipl\Orm\Model;

abstract class ObjectInspectionDetail extends BaseHtmlElement
{
    use IcingaRedis;

    protected $tag = 'div';

    protected $defaultAttributes = ['class' => 'inspection-detail'];

    /** @var Model */
    protected $object;

    /** @var array */
    protected $attrs;

    /** @var array */
    protected $joins;

    public function __construct(Model $object, array $apiResult)
    {
        $this->object = $object;
        $this->attrs = $apiResult['attrs'];
        $this->joins = $apiResult['joins'];
    }

    protected function createSourceLocation()
    {
        if (! isset($this->attrs['source_location'])) {
            return;
        }

        return [
            new HtmlElement('h2', null, Text::create(t('Source Location'))),
            FormattedString::create(
                t('You can find this object in %s on line %s.'),
                new HtmlElement('strong', null, Text::create($this->attrs['source_location']['path'])),
                new HtmlElement('strong', null, Text::create($this->attrs['source_location']['first_line']))
            )
        ];
    }

    protected function createLastCheckResult()
    {
        if (! isset($this->attrs['last_check_result'])) {
            return;
        }

        $command = $this->attrs['last_check_result']['command'];
        if (is_array($command)) {
            $command = join(' ', array_map('escapeshellarg', $command));
        }

        $blacklist = [
            'command',
            'output',
            'type',
            'active'
        ];

        return [
            new HtmlElement('h2', null, Text::create(t('Executed Command'))),
            new HtmlElement('pre', null, Text::create($command)),
            new HtmlElement('h2', null, Text::create(t('Execution Details'))),
            $this->createNameValueTable(
                array_diff_key($this->attrs['last_check_result'], array_flip($blacklist)),
                [
                    'execution_end'     => [$this, 'formatTimestamp'],
                    'execution_start'   => [$this, 'formatTimestamp'],
                    'schedule_end'      => [$this, 'formatTimestamp'],
                    'schedule_start'    => [$this, 'formatTimestamp'],
                    'ttl'               => [$this, 'formatSeconds'],
                    'state'             => [$this, 'formatState']
                ]
            )
        ];
    }

    protected function createRedisInfo()
    {
        $title = new HtmlElement('h2', null, Text::create(t('Volatile State Details')));

        try {
            $json = $this->getIcingaRedis()
                ->hGet("icinga:{$this->object->getTableName()}:state", bin2hex($this->object->id));
        } catch (Exception $e) {
            return [$title, sprintf('Failed to load redis data: %s', $e->getMessage())];
        }

        if ($json === false) {
            return [$title, new EmptyState(t('No data available in redis'))];
        }

        try {
            $data = Json::decode($json, true);
        } catch (JsonDecodeException $e) {
            return [$title, sprintf('Failed to decode redis data: %s', $e->getMessage())];
        }

        $blacklist = [
            'commandline',
            'environment_id',
            'id'
        ];

        return [$title, $this->createNameValueTable(
            array_diff_key($data, array_flip($blacklist)),
            [
                'last_state_change'     => [$this, 'formatMillisecondTimestamp'],
                'last_update'           => [$this, 'formatMillisecondTimestamp'],
                'next_check'            => [$this, 'formatMillisecondTimestamp'],
                'next_update'           => [$this, 'formatMillisecondTimestamp'],
                'check_timeout'         => [$this, 'formatMilliseconds'],
                'execution_time'        => [$this, 'formatMilliseconds'],
                'latency'               => [$this, 'formatMilliseconds'],
                'hard_state'            => [$this, 'formatState'],
                'previous_hard_state'   => [$this, 'formatState'],
                'state'                 => [$this, 'formatState']
            ]
        )];
    }

    protected function createAttributes()
    {
        $blacklist = [
            'name',
            '__name',
            'host_name',
            'display_name',
            'last_check_result',
            'source_location',
            'templates',
            'package',
            'version',
            'type',
            'active',
            'paused',
            'ha_mode'
        ];

        return [
            new HtmlElement('h2', null, Text::create(t('Object Attributes'))),
            $this->createNameValueTable(
                array_diff_key($this->attrs, array_flip($blacklist)),
                [
                    'acknowledgement_expiry'        => [$this, 'formatTimestamp'],
                    'acknowledgement_last_change'   => [$this, 'formatTimestamp'],
                    'check_timeout'                 => [$this, 'formatSeconds'],
                    'flapping_last_change'          => [$this, 'formatTimestamp'],
                    'last_check'                    => [$this, 'formatTimestamp'],
                    'last_hard_state_change'        => [$this, 'formatTimestamp'],
                    'last_state_change'             => [$this, 'formatTimestamp'],
                    'last_state_ok'                 => [$this, 'formatTimestamp'],
                    'last_state_up'                 => [$this, 'formatTimestamp'],
                    'last_state_warning'            => [$this, 'formatTimestamp'],
                    'last_state_critical'           => [$this, 'formatTimestamp'],
                    'last_state_down'               => [$this, 'formatTimestamp'],
                    'last_state_unknown'            => [$this, 'formatTimestamp'],
                    'last_state_unreachable'        => [$this, 'formatTimestamp'],
                    'next_check'                    => [$this, 'formatTimestamp'],
                    'next_update'                   => [$this, 'formatTimestamp'],
                    'previous_state_change'         => [$this, 'formatTimestamp'],
                    'check_interval'                => [$this, 'formatSeconds'],
                    'retry_interval'                => [$this, 'formatSeconds'],
                    'last_hard_state'               => [$this, 'formatState'],
                    'last_state'                    => [$this, 'formatState'],
                    'state'                         => [$this, 'formatState']
                ]
            )
        ];
    }

    private function formatJson($json)
    {
        if (is_scalar($json)) {
            return Json::encode($json, JSON_UNESCAPED_SLASHES);
        }

        return new HtmlElement(
            'pre',
            null,
            Text::create(Json::encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))
        );
    }

    private function formatTimestamp($ts)
    {
        if ($ts === 0) {
            return '-';
        }

        if (is_float($ts)) {
            $dt = DateTime::createFromFormat('U.u', $ts);
        } else {
            $dt = (new DateTime())->setTimestamp($ts);
        }

        return $dt->setTimezone(new DateTimeZone('UTC'))
            ->format('Y-m-d\TH:i:s.vP');
    }

    private function formatMillisecondTimestamp($ms)
    {
        return $this->formatTimestamp($ms / 1000.0);
    }

    private function formatSeconds($s)
    {
        return Format::seconds($s);
    }

    private function formatMilliseconds($ms)
    {
        return Format::seconds($ms / 1000.0);
    }

    private function formatState($state)
    {
        switch (true) {
            case $this->object instanceof Host:
                return HostStates::text($state);
            case $this->object instanceof Service:
                return ServiceStates::text($state);
            default:
                return $state;
        }
    }

    private function createNameValueTable(array $data, array $formatters)
    {
        $table = new Table();
        $table->addAttributes(['class' => 'name-value-table']);
        foreach ($data as $name => $value) {
            if (empty($value) && ($value === null || is_string($value) || is_array($value))) {
                $value = '-';
            } elseif (isset($formatters[$name])) {
                $value = call_user_func($formatters[$name], $value);
            } else {
                $value = $this->formatJson($value);
            }

            $table->addHtml(Table::tr([
                Table::th($name),
                Table::td($value)
            ]));
        }

        return $table;
    }
}
