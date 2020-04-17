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

class ApiTransportStep extends Step
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
        $transportConfig = $this->data;
        $transportName = $transportConfig['name'];
        unset($transportConfig['name']);

        try {
            $config = Config::module('monitoring', 'commandtransports', true);
            $config->setSection($transportName, $transportConfig);
            $config->saveIni();
        } catch (Exception $e) {
            $this->error = $e;
            return false;
        }

        return true;
    }

    public function getSummary()
    {
        $description = new HtmlElement('p', null, mt(
            'icingadb',
            'The Icinga 2 API will be accessed using the following connection details:'
        ));

        $apiOptions = new Table();
        $apiOptions->add(Table::row([
            new HtmlElement('strong', null, t('Host')),
            $this->data['host']
        ]));
        $apiOptions->add(Table::row([
            new HtmlElement('strong', null, t('Port')),
            $this->data['port']
        ]));
        $apiOptions->add(Table::row([
            new HtmlElement('strong', null, t('Username')),
            $this->data['username']
        ]));
        $apiOptions->add(Table::row([
            new HtmlElement('strong', null, t('Password')),
            str_repeat('*', strlen($this->data['password']))
        ]));

        $topic = new HtmlElement('div', ['class' => 'topic']);
        $topic->add([$description, $apiOptions]);

        $summary = new HtmlDocument();
        $summary->add([
            new HtmlElement('h2', null, mt('icingadb', 'Icinga 2 API')),
            $topic
        ]);

        return $summary->render();
    }

    public function getReport()
    {
        if ($this->error === null) {
            return [sprintf(
                mt('icingadb', 'Commandtransport configuration update successful: %s'),
                Config::module('monitoring', 'commandtransports')->getConfigFile()
            )];
        } else {
            return [
                sprintf(
                    mt('icingadb', 'Commandtransport configuration update failed: %s'),
                    Config::module('monitoring', 'commandtransports')->getConfigFile()
                ),
                sprintf(mt('setup', 'ERROR: %s'), IcingaException::describe($this->error))
            ];
        }
    }
}
