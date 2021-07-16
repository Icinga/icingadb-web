<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Setup;

use Exception;
use Icinga\Application\Config;
use Icinga\Application\Icinga;
use Icinga\Exception\IcingaException;
use Icinga\Exception\NotWritableError;
use Icinga\File\Storage\LocalFileStorage;
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
            'redis'  => [
                'tls' => 0
            ],
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

        if (isset($this->data['redis_tls']) && $this->data['redis_tls']) {
            $moduleConfig['redis']['tls'] = 1;
            if (isset($this->data['redis_insecure']) && $this->data['redis_insecure']) {
                $moduleConfig['redis']['insecure'] = 1;
            }

            $storage = new LocalFileStorage(Icinga::app()->getStorageDir(
                join(DIRECTORY_SEPARATOR, ['modules', 'icingadb', 'redis'])
            ));
            foreach (['ca', 'cert', 'key'] as $name) {
                $textareaName = 'redis_' . $name . '_pem';
                if (isset($this->data[$textareaName]) && $this->data[$textareaName]) {
                    $pem = $this->data[$textareaName];
                    $pemFile = md5($pem) . '-' . $name . '.pem';
                    if (! $storage->has($pemFile)) {
                        try {
                            $storage->create($pemFile, $pem);
                        } catch (NotWritableError $e) {
                            $this->error = $e;
                            return false;
                        }
                    }

                    $moduleConfig['redis'][$name] = $storage->resolvePath($pemFile);
                }
            }
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

        $tlsOptions = new Table();
        $topic->addHtml($tlsOptions);
        if (isset($this->data['redis_tls']) && $this->data['redis_tls']) {
            if (isset($this->data['redis_cert_pem']) && $this->data['redis_cert_pem']) {
                $tlsOptions->addHtml(Table::row([
                    new HtmlElement('strong', null, Text::create('TLS')),
                    Text::create(
                        t('Icinga DB Web will authenticate against Redis with a client'
                          . ' certificate and private key over a secured connection')
                    )
                ]));
            } else {
                $tlsOptions->addHtml(Table::row([
                    new HtmlElement('strong', null, Text::create('TLS')),
                    Text::create(t('Icinga DB Web will use secured Redis connections'))
                ]));
            }
        } else {
            $tlsOptions->addHtml(Table::row([
                new HtmlElement('strong', null, Text::create('TLS')),
                Text::create(t('No'))
            ]));
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
