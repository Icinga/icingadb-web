<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Setup;

use Exception;
use Icinga\Application\Config;
use Icinga\Exception\IcingaException;
use Icinga\Module\Setup\Step;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlElement;
use ipl\Html\Table;

class RedisStep extends Step
{
    /** @var array */
    protected $data;

    /** @var Exception */
    protected $error;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function apply()
    {
        $moduleConfig = [
            'redis1' => [
                'host'  => $this->data['redis1_host'],
                'port'  => $this->data['redis1_port'] ?: null
            ]
        ];

        if (isset($this->data['redis2_host']) && $this->data['redis2_host']) {
            $moduleConfig['redis2'] = [
                'host'  => $this->data['redis2_host'],
                'port'  => $this->data['redis2_port'] ?: null
            ];
        }

        try {
            $config = Config::module('icingadb', 'config', true);
            foreach ($moduleConfig as $section => $options) {
                $config->setSection($section, $options);
            }

            $config->saveIni();
        } catch (Exception $e) {
            $this->error = $e;
            return false;
        }

        return true;
    }

    public function getSummary()
    {
        $topic = new HtmlElement('div', ['class' => 'topic']);
        $topic->add(new HtmlElement('p', null, mt(
            'icingadb',
            'The Icinga DB Redis will be accessed using the following connection details:'
        )));

        $primaryOptions = new Table();
        $primaryOptions->add(Table::row([
            new HtmlElement('strong', null, t('Host')),
            $this->data['redis1_host']
        ]));
        $primaryOptions->add(Table::row([
            new HtmlElement('strong', null, t('Port')),
            $this->data['redis1_port'] ?: 6380
        ]));

        if (isset($this->data['redis2_host']) && $this->data['redis2_host']) {
            $topic->add([
                new HtmlElement('h3', null, mt('icingadb', 'Primary')),
                $primaryOptions
            ]);

            $secondaryOptions = new Table();
            $secondaryOptions->add(Table::row([
                new HtmlElement('strong', null, t('Host')),
                $this->data['redis2_host']
            ]));
            $secondaryOptions->add(Table::row([
                new HtmlElement('strong', null, t('Port')),
                $this->data['redis2_port'] ?: 6380
            ]));

            $topic->add([
                new HtmlElement('h3', null, mt('icingadb', 'Secondary')),
                $secondaryOptions
            ]);
        } else {
            $topic->add($primaryOptions);
        }

        $summary = new HtmlDocument();
        $summary->add([
            new HtmlElement('h2', null, mt('icingadb', 'Icinga DB Redis')),
            $topic
        ]);

        return $summary->render();
    }

    public function getReport()
    {
        if ($this->error === null) {
            return [sprintf(
                mt('icingadb', 'Module configuration update successful: %s'),
                Config::module('icingab')->getConfigFile()
            )];
        } else {
            return [
                sprintf(
                    mt('icingadb', 'Module configuration update failed: %s'),
                    Config::module('icingab')->getConfigFile()
                ),
                sprintf(mt('setup', 'ERROR: %s'), IcingaException::describe($this->error))
            ];
        }
    }
}
