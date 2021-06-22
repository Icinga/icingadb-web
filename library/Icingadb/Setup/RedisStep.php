<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Setup;

use Exception;
use Icinga\Application\Config;
use Icinga\Exception\IcingaException;
use Icinga\Module\Setup\Step;
use ipl\Html\Attributes;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlElement;
use ipl\Html\Table;
use ipl\Html\Text;

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
        $topic = new HtmlElement('div', Attributes::create(['class' => 'topic']));
        $topic->addHtml(new HtmlElement('p', null, Text::create(mt(
            'icingadb',
            'The Icinga DB Redis will be accessed using the following connection details:'
        ))));

        $primaryOptions = new Table();
        $primaryOptions->addHtml(Table::row([
            new HtmlElement('strong', null, Text::create(t('Host'))),
            $this->data['redis1_host']
        ]));
        $primaryOptions->addHtml(Table::row([
            new HtmlElement('strong', null, Text::create(t('Port'))),
            $this->data['redis1_port'] ?: 6380
        ]));

        if (isset($this->data['redis2_host']) && $this->data['redis2_host']) {
            $topic->addHtml(
                new HtmlElement('h3', null, Text::create(mt('icingadb', 'Primary'))),
                $primaryOptions
            );

            $secondaryOptions = new Table();
            $secondaryOptions->addHtml(Table::row([
                new HtmlElement('strong', null, Text::create(t('Host'))),
                $this->data['redis2_host']
            ]));
            $secondaryOptions->addHtml(Table::row([
                new HtmlElement('strong', null, Text::create(t('Port'))),
                $this->data['redis2_port'] ?: 6380
            ]));

            $topic->addHtml(
                new HtmlElement('h3', null, Text::create(mt('icingadb', 'Secondary'))),
                $secondaryOptions
            );
        } else {
            $topic->addHtml($primaryOptions);
        }

        $summary = new HtmlDocument();
        $summary->addHtml(
            new HtmlElement('h2', null, Text::create(mt('icingadb', 'Icinga DB Redis'))),
            $topic
        );

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
