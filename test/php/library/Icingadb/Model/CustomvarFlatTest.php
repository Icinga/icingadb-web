<?php

/* Icinga DB Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Tests\Icinga\Modules\Icingadb\Model;

use Icinga\Module\Icingadb\Model\CustomvarFlat;
use PHPUnit\Framework\TestCase;

class CustomvarFlatTest extends TestCase
{
    const EMPTY_TEST_SOURCE = [
        ["dict.not_empty.foo","bar","dict","{\"empty\":{},\"not_empty\":{\"foo\":\"bar\"}}"],
        ["dict.empty",null,"dict","{\"empty\":{},\"not_empty\":{\"foo\":\"bar\"}}"],
        ["list[1]",null,"list","[[\"foo\",\"bar\"],[]]"],
        ["list[0][0]","foo","list","[[\"foo\",\"bar\"],[]]"],
        ["list[0][1]","bar","list","[[\"foo\",\"bar\"],[]]"],
        ["empty_list",null,"empty_list","[]"],
        ["empty_dict",null,"empty_dict","{}"],
        ["null","null","null","null"]
    ];

    const EMPTY_TEST_RESULT = [
        "dict" => [
            "not_empty" => [
                "foo" => "bar"
            ],
            "empty" => []
        ],
        "list" => [
            ["foo", "bar"],
            []
        ],
        "empty_list" => [],
        "empty_dict" => [],
        "null" => "null"
    ];

    const SPECIAL_CHAR_TEST_SOURCE = [
        [
            "vhosts.xxxxxxxxxxxxx.mgmt.xxxxxx.com.http_port",
            "443",
            "vhosts",
            "{\"xxxxxxxxxxxxx.mgmt.xxxxxx.com\":{\"http_port\":\"443\"}}"
        ],
        ["ex.ample.com.bla","blub","ex","{\"ample.com\":{\"bla\":\"blub\"}}"],
        ["example[1]","zyx","example[1]","\"zyx\""],
        ["example.0.org","xyz","example.0.org","\"xyz\""],
        ["ob.je.ct","***","ob","{\"je\":{\"ct\":\"tcejbo\"}}"],
        ["real_list[2]","three","real_list","[\"one\",\"two\",\"three\"]"],
        ["real_list[1]","two","real_list","[\"one\",\"two\",\"three\"]"],
        ["real_list[0]","one","real_list","[\"one\",\"two\",\"three\"]"],
        ["[1].2.[3].4.[5].6","123456","[1].2","{\"[3].4\":{\"[5].6\":123456}}"],
        ["ex.ample.com","cba","ex.ample.com","\"cba\""],
        ["[4]","four","[4]","\"four\""]
    ];

    const SPECIAL_CHAR_TEST_RESULT = [
        "vhosts" => [
            "xxxxxxxxxxxxx.mgmt.xxxxxx.com" => [
                "http_port" => 443
            ]
        ],
        "ex" => [
            "ample.com" => [
                "bla" => "blub"
            ]
        ],
        "example[1]" => "zyx",
        "example.0.org" => "xyz",
        "ob" => [
            "je" => [
                "ct" => "***"
            ]
        ],
        "real_list" => [
            "one",
            "two",
            "three"
        ],
        "[1].2" => [
            "[3].4" => [
                "[5].6" => "123456"
            ]
        ],
        "ex.ample.com" => "cba",
        "[4]" => "four"
    ];

    public function testUnflatteningOfEmptyCustomVariables()
    {
        $this->assertEquals(
            self::EMPTY_TEST_RESULT,
            (new CustomvarFlat())->unFlattenVars($this->transformSource(self::EMPTY_TEST_SOURCE)),
            "Empty custom variables are not correctly unflattened"
        );
    }

    public function testUnflatteningOfCustomVariablesWithSpecialCharacters()
    {
        $this->assertEquals(
            self::SPECIAL_CHAR_TEST_RESULT,
            (new CustomvarFlat())->unFlattenVars($this->transformSource(self::SPECIAL_CHAR_TEST_SOURCE)),
            "Custom variables with special characters are not correctly unflattened"
        );
    }

    protected function transformSource(array $source): \Generator
    {
        foreach ($source as $data) {
            yield (object) [
                'flatname' => $data[0],
                'flatvalue' => $data[1],
                'customvar' => (object) [
                    'name' => $data[2],
                    'value' => $data[3]
                ]
            ];
        }
    }
}
