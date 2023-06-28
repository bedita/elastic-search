<?php
declare(strict_types=1);

namespace BEdita\ElasticSearch\Test\Fixture;

use BEdita\Core\Test\Fixture\ObjectTypesFixture as BEditaObjectTypesFixture;

/**
 * Fixture for object types.
 */
class ObjectTypesFixture extends BEditaObjectTypesFixture
{
    /**
     * Records
     *
     * @var array
     */
    public $records = [
        // 1
        [
            'singular' => 'object',
            'name' => 'objects',
            'is_abstract' => true,
            'parent_id' => null,
            'tree_left' => 1,
            'tree_right' => 20,
            'description' => null,
            'plugin' => 'BEdita/Core',
            'model' => 'Objects',
            'created' => '2017-11-10 09:27:23',
            'modified' => '2017-11-10 09:27:23',
            'enabled' => true,
            'core_type' => true,
        ],
        // 2
        [
            'singular' => 'user',
            'name' => 'users',
            'is_abstract' => false,
            'parent_id' => 1,
            'tree_left' => 2,
            'tree_right' => 3,
            'description' => null,
            'plugin' => 'BEdita/Core',
            'model' => 'Users',
            'hidden' => '["birthdate","body","company","company_name","company_kind","deathdate","description","national_id_number","person_title","publish_end","publish_start","state_name","vat_number","website"]',
            'created' => '2017-11-10 09:27:23',
            'modified' => '2017-11-10 09:27:23',
            'enabled' => true,
            'core_type' => true,
        ],
        // 3
        [
            'singular' => 'classroom',
            'name' => 'classrooms',
            'is_abstract' => false,
            'parent_id' => 1,
            'tree_left' => 4,
            'tree_right' => 5,
            'description' => null,
            'plugin' => 'Zanichelli/Classrooms',
            'model' => 'Classrooms',
            'hidden' => '["body","description","translations"]',
            'created' => '2020-12-10 17:30:00',
            'modified' => '2020-12-10 17:30:00',
            'enabled' => true,
            'core_type' => false,
        ],
        // 4
        [
            'singular' => 'message',
            'name' => 'messages',
            'is_abstract' => false,
            'parent_id' => 1,
            'tree_left' => 6,
            'tree_right' => 7,
            'description' => null,
            'plugin' => 'Zanichelli/Classrooms',
            'model' => 'Messages',
            'hidden' => '["description","translations"]',
            'created' => '2020-12-10 17:30:00',
            'modified' => '2020-12-10 17:30:00',
            'enabled' => true,
            'core_type' => false,
        ],
        // 5
        [
            'singular' => 'link',
            'name' => 'links',
            'is_abstract' => false,
            'parent_id' => 1,
            'tree_left' => 8,
            'tree_right' => 9,
            'description' => null,
            'plugin' => 'BEdita/Core',
            'model' => 'Links',
            'created' => '2020-12-10 17:30:00',
            'modified' => '2020-12-10 17:30:00',
            'enabled' => true,
            'core_type' => true,
        ],
    ];
}
