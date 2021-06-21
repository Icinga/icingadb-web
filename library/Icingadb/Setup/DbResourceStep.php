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

class DbResourceStep extends Step
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
        $resourceConfig = $this->data;
        $resourceName = $resourceConfig['name'];
        unset($resourceConfig['name']);

        try {
            $config = Config::app('resources', true);
            $config->setSection($resourceName, $resourceConfig);
            $config->saveIni();
        } catch (Exception $e) {
            $this->error = $e;
            return false;
        }

        try {
            $config = Config::module('icingadb', 'config', true);
            $config->setSection('icingadb', ['resource' => $resourceName]);
            $config->saveIni();
        } catch (Exception $e) {
            $this->error = $e;
            return false;
        }

        return true;
    }

    public function getSummary()
    {
        $description = new HtmlElement('p', null, Text::create(mt(
            'icingadb',
            'Icinga DB will be accessed using the following connection details:'
        )));

        $resourceOptions = new Table();
        $resourceOptions->addHtml(Table::row([
            new HtmlElement('strong', null, Text::create(t('Host'))),
            $this->data['host']
        ]));
        $resourceOptions->addHtml(Table::row([
            new HtmlElement('strong', null, Text::create(t('Port'))),
            $this->data['port'] ?: ($this->data['db'] === 'mysql' ? 3306 : 5432)
        ]));
        $resourceOptions->addHtml(Table::row([
            new HtmlElement('strong', null, Text::create(t('Database'))),
            $this->data['dbname']
        ]));
        $resourceOptions->addHtml(Table::row([
            new HtmlElement('strong', null, Text::create(t('Username'))),
            $this->data['username']
        ]));
        $resourceOptions->addHtml(Table::row([
            new HtmlElement('strong', null, Text::create(t('Password'))),
            str_repeat('*', strlen($this->data['password']))
        ]));
        $resourceOptions->addHtml(Table::row([
            new HtmlElement('strong', null, Text::create(t('Charset'))),
            $this->data['charset']
        ]));

        if (isset($this->data['use_ssl']) && $this->data['use_ssl']) {
            $resourceOptions->addHtml(Table::row([
                new HtmlElement('strong', null, Text::create(t('SSL Do Not Verify Server Certificate'))),
                isset($this->data['ssl_do_not_verify_server_cert']) && $this->data['ssl_do_not_verify_server_cert']
                    ? t('Yes')
                    : t('No')
            ]));
            $resourceOptions->addHtml(Table::row([
                new HtmlElement('strong', null, Text::create(t('SSL Key'))),
                $this->data['ssl_key'] ?: mt('icingadb', 'None', 'non-existence of a value')
            ]));
            $resourceOptions->addHtml(Table::row([
                new HtmlElement('strong', null, Text::create(t('SSL Certificate'))),
                $this->data['ssl_cert'] ?: mt('icingadb', 'None', 'non-existence of a value')
            ]));
            $resourceOptions->addHtml(Table::row([
                new HtmlElement('strong', null, Text::create(t('SSL CA'))),
                $this->data['ssl_ca'] ?: mt('icingadb', 'None', 'non-existence of a value')
            ]));
            $resourceOptions->addHtml(Table::row([
                new HtmlElement('strong', null, Text::create(t('The CA certificate file path'))),
                $this->data['ssl_capath'] ?: mt('icingadb', 'None', 'non-existence of a value')
            ]));
            $resourceOptions->addHtml(Table::row([
                new HtmlElement('strong', null, Text::create(t('SSL CA Path'))),
                $this->data['ssl_cipher'] ?: mt('icingadb', 'None', 'non-existence of a value')
            ]));
        }

        $topic = new HtmlElement('div', Attributes::create(['class' => 'topic']));
        $topic->addHtml($description, $resourceOptions);

        $summary = new HtmlDocument();
        $summary->addHtml(
            new HtmlElement('h2', null, Text::create(mt('icingadb', 'Icinga DB Resource'))),
            $topic
        );

        return $summary->render();
    }

    public function getReport()
    {
        if ($this->error === null) {
            return [sprintf(
                mt('icingadb', 'Resource configuration update successful: %s'),
                Config::resolvePath('resources.ini')
            )];
        } else {
            return [
                sprintf(
                    mt('icingadb', 'Resource configuration update failed: %s'),
                    Config::resolvePath('resources.ini')
                ),
                sprintf(mt('setup', 'ERROR: %s'), IcingaException::describe($this->error))
            ];
        }
    }
}
