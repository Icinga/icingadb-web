<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Common;

use Exception;
use Icinga\Exception\Json\JsonDecodeException;
use Icinga\Util\Json;
use ipl\Html\BaseHtmlElement;
use ipl\Html\FormattedString;
use ipl\Html\HtmlElement;
use ipl\Html\Table;
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
            new HtmlElement('h2', null, t('Source Location')),
            FormattedString::create(
                t('You can find this object in %s on line %s.'),
                new HtmlElement('strong', null, $this->attrs['source_location']['path']),
                new HtmlElement('strong', null, $this->attrs['source_location']['first_line'])
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
            new HtmlElement('h2', null, t('Executed Command')),
            new HtmlElement('pre', null, $command),
            new HtmlElement('h2', null, t('Execution Details')),
            $this->createNameValueTable(array_diff_key($this->attrs['last_check_result'], array_flip($blacklist)))
        ];
    }

    protected function createRedisInfo()
    {
        $title = new HtmlElement('h2', null, t('Volatile State Details'));

        try {
            $json = $this->getIcingaRedis()
                ->hGet("icinga:config:state:{$this->object->getTableName()}", bin2hex($this->object->id));
        } catch (Exception $e) {
            return [$title, sprintf('Failed to load redis data: %s', $e->getMessage())];
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

        return [$title, $this->createNameValueTable(array_diff_key($data, array_flip($blacklist)))];
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
            new HtmlElement('h2', null, t('Object Attributes')),
            $this->createNameValueTable(array_diff_key($this->attrs, array_flip($blacklist)))
        ];
    }

    private function createNameValueTable(array $data)
    {
        $table = new Table();
        $table->addAttributes(['class' => 'name-value-table']);
        foreach ($data as $name => $value) {
            if (empty($value) && ($value === null || is_string($value) || is_array($value))) {
                $value = '-';
            } elseif (is_array($value)) {
                $value = new HtmlElement(
                    'pre',
                    null,
                    Json::encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
                );
            } elseif (is_scalar($value)) {
                $value = Json::encode($value, JSON_UNESCAPED_SLASHES);
            }

            $table->add(Table::tr([
                Table::th($name),
                Table::td($value)
            ]));
        }

        return $table;
    }
}
