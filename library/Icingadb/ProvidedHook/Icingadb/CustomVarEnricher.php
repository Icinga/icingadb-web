<?php

namespace Icinga\Module\Icingadb\ProvidedHook\Icingadb;

use Icinga\Application\Config;
use Icinga\Module\Director\Data\Db\DbConnection;
use Icinga\Module\Director\DataType\DataTypeDatalist;
use Icinga\Module\Director\DataType\DataTypeDictionary;
use Icinga\Module\Director\Db;
use Icinga\Module\Director\Objects\DirectorDatafield;
use Icinga\Module\Director\Objects\IcingaHost;
use Icinga\Module\Director\Objects\IcingaService;
use Icinga\Module\Director\Web\Form\IcingaObjectFieldLoader;
use Icinga\Module\Icingadb\Hook\CustomVarEnricherHook;
use Icinga\Module\Icingadb\Model\Host;
use Icinga\Module\Icingadb\Model\Service;
use ipl\Orm\Model;

class CustomVarEnricher extends CustomVarEnricherHook
{
    protected $fieldConfig;

    protected $datalistMaps = [];

    protected $groups = [];

    public function enrichCustomVars(array &$vars, Model $object): array
    {
        $directorObject = null;
        $connection =  Db::fromResourceName(Config::module('director')->get('db', 'resource'));
        if ($object instanceof Host) {
            $directorObject = IcingaHost::load($object->name, $connection);
        } elseif ($object instanceof Service) {
            $directorHost = IcingaHost::load($object->host->name, $connection);
            $directorObject = IcingaService::load(
                ['object_name' => $object->name, 'host_id' => $directorHost->get('id')],
                $connection
            );
        }

        $newVars = [];
        $this->fieldConfig = (new IcingaObjectFieldLoader($directorObject))->getFields();

        $this->buildDataListMap($connection);

        if ($directorObject) {
            $varsToReplace = json_decode(json_encode($directorObject->getVars()), true)
                + json_decode(json_encode($directorObject->getInheritedVars()), true);

            foreach ($varsToReplace as $varName => $customVar) {
                if (isset($vars[$varName])) {
                    $newVars[] = $this->resolveCustomVarMapping($varName, $customVar, $connection);

                    unset($vars[$varName]);
                }
            }

            $newVars = array_merge([], ...$newVars);
        }

        return $newVars;
    }

    /**
     * Returns the resolved mapping to custom variables in Director
     *
     * @param string $name
     * @param $val
     * @param DbConnection $conn
     * @param bool $grouping Whether to enable grouping of custom variables into sections
     *
     * @return array
     */
    protected function resolveCustomVarMapping(?string $name, $val, DbConnection $conn, bool $grouping = true): array
    {
        if (isset($this->fieldConfig[$name])) {
            /** @var DirectorDatafield $field */
            $field = $this->fieldConfig[$name];
            $dataType = $field->get('datatype');

            if ($dataType === get_class(new DataTypeDictionary())) {
                $label = $field->get('caption');
                $newVarValue = [];
                foreach ($val as $nestedVarName => $nestedVarValue) {
                    $newVarValue[$nestedVarName] = $this->buildDictionaryMap(
                        $nestedVarValue,
                        $conn
                    );
                }

                return [$label => $newVarValue];
            } elseif ($dataType === get_class(new DataTypeDatalist())) {
                if (isset($this->datalistMaps[$name])) {
                    $val = $this->datalistMaps[$name][$val];
                }

                $name = $field->get('caption');
            } else {
                $name = $field->get('caption');
            }

            if ($grouping && $field->get('category_id') !== null) {
                if (! isset($this->groups[$field->getCategoryName()])) {
                    $this->groups[$field->getCategoryName()] = [$name => $val];
                } else {
                    $this->groups[$field->getCategoryName()][$name] = $val;
                }

                return [];
            }
        } elseif (is_array($val)) {
            $newValue = [];
            foreach ($val as $childName => $childValue) {
                $newValue[] = $this->resolveCustomVarMapping($childName, $childValue, $conn, false);
            }

            $val = array_merge([], ...$newValue);
        }

        return [$name => $val];
    }

    private function buildDictionaryMap($val, DbConnection $connection): array
    {
        foreach ($val as $childName => $childValue) {
            $newValue[] = $this->resolveCustomVarMapping($childName, $childValue, $connection, false);
        }

        return array_merge([], ...$newValue);
    }

    private function buildDataListMap(DbConnection $db)
    {
        $fieldsWithDataLists = [];
        foreach ($this->fieldConfig as $field) {
            if ($field->get('datatype') === 'Icinga\Module\Director\DataType\DataTypeDatalist') {
                $fieldsWithDataLists[$field->get('id')] = $field;
            }
        }

        if (! empty($fieldsWithDataLists)) {
            $dataListEntries = $db->select()->from(
                ['dds' => 'director_datafield_setting'],
                [
                    'dds.datafield_id',
                    'dde.entry_name',
                    'dde.entry_value'
                ]
            )->join(
                ['dde' => 'director_datalist_entry'],
                'CAST(dds.setting_value AS integer) = dde.list_id',
                []
            )->where('dds.datafield_id', array_keys($fieldsWithDataLists))
                ->where('dds.setting_name', 'datalist_id');

            foreach ($dataListEntries as $dataListEntry) {
                $field = $fieldsWithDataLists[$dataListEntry->datafield_id];
                $this->datalistMaps[$field->get('varname')][$dataListEntry->entry_name] = $dataListEntry->entry_value;
            }
        }
    }

    public function getGroups(): array
    {
        return $this->groups;
    }
}
