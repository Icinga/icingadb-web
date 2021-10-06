<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Controllers;

use Icinga\Module\Icingadb\Common\IcingaRedis;
use Icinga\Module\Icingadb\Util\PluginOutput;
use Icinga\Module\Icingadb\Web\Controller;
use Icinga\Module\Icingadb\Widget\EmptyState;
use Icinga\Module\Icingadb\Widget\PluginOutputContainer;
use Icinga\Web\Session;
use Icinga\Web\Window;
use ipl\Html\HtmlElement;

class StateController extends Controller
{
    public function updateAction()
    {
        $route = Session::getSession()->getNamespace('icingadb-state-updates')
            ->get(Window::getInstance()->getContainerId());
        $hostIds = explode(',', $this->getRequest()->getPost('hosts'));
        $serviceIds = explode(',', $this->getRequest()->getPost('services'));

        $hostStates = [];
        foreach (IcingaRedis::instance()->getConnection()->hmget("icinga:host:state", $hostIds) as $i => $json) {
            if ($json !== null) {
                $json = json_decode($json, true);
            }

            $hostStates[$hostIds[$i]] = $json;
        }

        $serviceStates = [];
        foreach (IcingaRedis::instance()->getConnection()->hmget("icinga:service:state", $serviceIds) as $i => $json) {
            if ($json !== null) {
                $json = json_decode($json, true);
            }

            $serviceStates[$serviceIds[$i]] = $json;
        }

        switch ($route) {
            case 'icingadb/service':
            case 'icingadb/services':
                foreach ($serviceIds as $serviceId) {
                    if (empty($serviceStates[$serviceId]['output'])) {
                        $this->addPart(
                            new EmptyState(t('Output unavailable.')),
                            $this->getRequest()->protectId($serviceId . '-output')
                        );
                    } else {
                        $this->addPart(HtmlElement::create(
                            'div',
                            ['id' => $this->getRequest()->protectId($serviceId . '-output')],
                            new PluginOutputContainer(new PluginOutput($serviceStates[$serviceId]['output']))
                        ));
                    }
                }

                break;
            default:
                exit;
        }
    }
}
