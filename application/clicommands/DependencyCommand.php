<?php

/* Icinga DB Web | (c) 2024 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Clicommands;

use Icinga\Application\Logger;
use Icinga\Cli\Command;
use Icinga\Module\Icingadb\Command\Object\GetDependenciesCommand;
use Icinga\Module\Icingadb\Command\Transport\CommandTransport;
use Icinga\Module\Icingadb\Common\Database;
use Icinga\Module\Icingadb\Model\Dependency;
use Icinga\Module\Icingadb\Model\Host;
use Icinga\Module\Icingadb\Model\RedundancyGroup;
use Icinga\Module\Icingadb\Model\Service;
use Icinga\Util\Environment;
use ipl\Stdlib\Filter;
use Throwable;

class DependencyCommand extends Command
{
    use Database;

    public function init(): void
    {
        Logger::getInstance()->setLevel(Logger::DEBUG);

        Environment::raiseExecutionTime(3600);
        Environment::raiseMemoryLimit();
    }

    public function setupAction(): void
    {
        $dependencies = (new CommandTransport())->send(new GetDependenciesCommand());

        $hosts = [];
        $services = [];
        $registry = [];
        $groups = [];
        foreach ($dependencies as $dependency) {
            $childName = $dependency['attrs']['child_host_name'];
            if (! empty($dependency['attrs']['child_service_name'])) {
                $childName .= '!' . $dependency['attrs']['child_service_name'];
                $services[$childName] = [
                    $dependency['attrs']['child_host_name'],
                    $dependency['attrs']['child_service_name']
                ];
            } else {
                $hosts[$childName] = $childName;
            }

            if (! isset($registry[$childName])) {
                $registry[$childName] = ['children' => []];
            }

            $parentName = $dependency['attrs']['parent_host_name'];
            if (! empty($dependency['attrs']['parent_service_name'])) {
                $parentName .= '!' . $dependency['attrs']['parent_service_name'];
                $services[$parentName] = [
                    $dependency['attrs']['parent_host_name'],
                    $dependency['attrs']['parent_service_name']
                ];
            } else {
                $hosts[$parentName] = $parentName;
            }

            $dependencyName = $dependency['name'];
            if (! isset($registry[$dependencyName])) {
                $registry[$dependencyName] = [
                    'dependency_id' => sha1($dependencyName, true),
                    'display_name' => $dependency['attrs']['name'],
                    'children' => []
                ];
            }

            if ($dependency['attrs']['redundancy_group']) {
                if (! isset($groups[$dependency['attrs']['redundancy_group']])) {
                    $groups[$dependency['attrs']['redundancy_group']] = [
                        $parentName => [
                            'ref' => null,
                            'children' => [$childName]
                        ]
                    ];
                } elseif (! isset($groups[$dependency['attrs']['redundancy_group']][$parentName])) {
                    $groups[$dependency['attrs']['redundancy_group']][$parentName] = [
                        'ref' => null,
                        'children' => [$childName]
                    ];
                } else {
                    $groups[$dependency['attrs']['redundancy_group']][$parentName]['children'][] = $childName;
                }

                $groupName = & $groups[$dependency['attrs']['redundancy_group']][$parentName]['ref'];
                if (! isset($registry[$parentName])) {
                    $registry[$parentName] = ['children' => [[& $groupName, $dependencyName]]];
                } else {
                    $registry[$parentName]['children'][] = [& $groupName, $dependencyName];
                }

                unset($groupName);
            } else {
                if (! isset($registry[$parentName])) {
                    $registry[$parentName] = ['children' => [[$childName, $dependencyName]]];
                } elseif (! in_array([$childName, $dependencyName], $registry[$parentName]['children'], true)) {
                    $registry[$parentName]['children'][] = [$childName, $dependencyName];
                }
            }
        }

        foreach ($groups as $name => $dependencies) {
            foreach ($dependencies as $parentName => $data) {
                sort($data['children']);
                $groupName = $name . '!' . implode('!', $data['children']);
                $data['ref'] = $groupName;
                if (! isset($registry[$groupName])) {
                    $registry[$groupName] = [
                        'redundancy_group_id' => sha1($groupName, true),
                        'display_name' => $name,
                        'children' => array_map(function ($child) {
                            return [$child, null];
                        }, $data['children'])
                    ];
                }
            }
        }

        $hostQ = Host::on($this->getDb())
            ->columns(['id', 'name'])
            ->filter(Filter::equal('host.name', array_values($hosts)));
        foreach ($hostQ as $host) {
            $registry[$host->name]['host_id'] = $host->id;
        }

        $serviceQ = Service::on($this->getDb())
            ->columns(['id', 'name', 'host.id', 'host.name']);
        $filter = Filter::any();
        foreach ($services as $serviceDef) {
            $filter->add(Filter::all(
                Filter::equal('service.host.name', $serviceDef[0]),
                Filter::equal('service.name', $serviceDef[1])
            ));
        }
        foreach ($serviceQ->filter($filter) as $service) {
            $name = $service->host->name . '!' . $service->name;
            $registry[$name]['host_id'] = $service->host->id;
            $registry[$name]['service_id'] = $service->id;
        }

        $getNodeId = function (string $name, array $data) use (&$registry, &$getNodeId): string {
            if (isset($registry[$name]['id'])) {
                return $registry[$name]['id'];
            }

            if (isset($data['host_id'])) {
                $id = $data['service_id'] ?? $data['host_id'];
                $this->getDb()->insert('dependency_node', [
                    'id' => $id,
                    'host_id' => $data['host_id'],
                    'service_id' => $data['service_id'] ?? null
                ]);
            } elseif (isset($data['redundancy_group_id'])) {
                $id = sha1('redundancy_group!' . $name, true);
                $this->getDb()->insert('redundancy_group', [
                    'id' => $data['redundancy_group_id'],
                    'name' => $name,
                    'display_name' => $data['display_name']
                ]);
                $this->getDb()->insert('dependency_node', [
                    'id' => $id,
                    'redundancy_group_id' => $data['redundancy_group_id']
                ]);
            } elseif (isset($data['dependency_id'])) {
                $id = $data['dependency_id'];
                $this->getDb()->insert('dependency', [
                    'id' => $data['dependency_id'],
                    'name' => $name,
                    'display_name' => $data['display_name']
                ]);
            }

            $registry[$name]['id'] = $id;

            return $id;
        };

        $this->getDb()->beginTransaction();

        foreach ($registry as $k => $data) {
            $id = $getNodeId($k, $data);
            $seenIds = [];
            foreach ($data['children'] as [$childName, $dependencyName]) {
                $childId = $getNodeId($childName, $registry[$childName]);
                if (isset($seenIds[$childId])) {
                    continue;
                }

                if (isset($registry[$childName]['redundancy_group_id'])) {
                    $seenIds[$childId] = true;
                }

                $dependencyId = $dependencyName !== null
                    ? $getNodeId($dependencyName, $registry[$dependencyName])
                    : null;
                try {
                    $this->getDb()->insert('dependency_edge', [
                        'from_node_id' => $childId,
                        'to_node_id' => $id,
                        'dependency_id' => $dependencyId
                    ]);
                } catch (Throwable $e) {
                    Logger::debug(
                        'Failed to insert edge %s <-> %s: %s',
                        bin2hex($childId),
                        bin2hex($id),
                        $e->getMessage()
                    );
                }
            }
        }

        $this->getDb()->commitTransaction();
    }

    public function syncStateAction(): void
    {
        $this->getDb()->beginTransaction();

        $dependencies = Dependency::on($this->getDb())
            ->columns([
                'id',
                'host_reachable' => 'edge.to.host.state.is_reachable',
                'host_problem' => 'edge.to.host.state.is_problem',
                'service_reachable' => 'edge.to.service.state.is_reachable',
                'service_problem' => 'edge.to.service.state.is_problem'
            ]);
        foreach ($dependencies as $dependency) {
            $failed = $dependency->host_reachable === 'n'
                || $dependency->host_problem === 'y'
                || $dependency->service_reachable === 'n'
                || $dependency->service_problem === 'y';

            try {
                $this->getDb()->insert('dependency_state', [
                    'id' => $dependency->id,
                    'dependency_id' => $dependency->id,
                    'failed' => $failed ? 'y' : 'n'
                ]);
            } catch (Throwable $e) {
                $this->getDb()->update(
                    'dependency_state',
                    ['failed' => $failed ? 'y' : 'n'],
                    ['id = ?' => $dependency->id]
                );
            }
        }

        $members = RedundancyGroup::on($this->getDb())
            ->columns([
                'id',
                'host_reachable' => 'dependency_node.parent.host.state.is_reachable',
                'host_problem' => 'dependency_node.parent.host.state.is_problem',
                'service_reachable' => 'dependency_node.parent.service.state.is_reachable',
                'service_problem' => 'dependency_node.parent.service.state.is_problem'
            ]);
        $groupState = [];
        foreach ($members as $member) {
            if (! isset($groupState[$member->id]) || $groupState[$member->id]) {
                $groupState[$member->id] = $member->host_reachable === 'n'
                    || $member->host_problem === 'y'
                    || $member->service_reachable === 'n'
                    || $member->service_problem === 'y';
            }
        }

        foreach ($groupState as $groupId => $failed) {
            try {
                $this->getDb()->insert('redundancy_group_state', [
                    'id' => $groupId,
                    'redundancy_group_id' => $groupId,
                    'failed' => $failed ? 'y' : 'n'
                ]);
            } catch (Throwable $e) {
                $this->getDb()->update(
                    'redundancy_group_state',
                    ['failed' => $failed ? 'y' : 'n'],
                    ['id = ?' => $groupId]
                );
            }
        }

        $this->getDb()->commitTransaction();
    }

    public function wipeAction(): void
    {
        $this->getDb()->beginTransaction();
        $this->getDb()->delete('dependency_edge');
        $this->getDb()->delete('dependency_node');
        $this->getDb()->delete('redundancy_group_state');
        $this->getDb()->delete('redundancy_group');
        $this->getDb()->delete('dependency_state');
        $this->getDb()->delete('dependency');
        $this->getDb()->commitTransaction();
    }
}
