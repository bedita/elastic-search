<?php
declare(strict_types=1);

namespace BEdita\ElasticSearch\Test\Fixture;

use BEdita\Core\Test\Fixture\ObjectsFixture as BEditaObjectsFixture;

/**
 * Fixture for objects.
 */
class ObjectsFixture extends BEditaObjectsFixture
{
    /**
     * Records
     *
     * @var array
     */
    public $records = [
        // 1
        [
            'object_type_id' => 2, // users
            'status' => 'on',
            'uname' => 'admin-user',
            'locked' => 1,
            'deleted' => 0,
            'created' => '2020-12-10 17:30:00',
            'modified' => '2020-12-10 17:30:00',
            'published' => null,
            'title' => 'Mr. Admin',
            'description' => null,
            'body' => null,
            'extra' => null,
            'lang' => 'en',
            'created_by' => 1,
            'modified_by' => 1,
        ],
        // 2
        [
            'object_type_id' => 2, // users
            'status' => 'on',
            'uname' => 'myz_1',
            'locked' => 0,
            'deleted' => 0,
            'created' => '2020-12-10 17:30:00',
            'modified' => '2020-12-10 17:30:00',
            'published' => null,
            'title' => 'Test Docente',
            'description' => null,
            'body' => null,
            'extra' => null,
            'lang' => 'it',
            'created_by' => 1,
            'modified_by' => 1,
        ],
        // 3
        [
            'object_type_id' => 2, // users
            'status' => 'on',
            'uname' => 'myz_2',
            'locked' => 0,
            'deleted' => 0,
            'created' => '2020-12-10 17:30:00',
            'modified' => '2020-12-10 17:30:00',
            'published' => null,
            'title' => 'Test Studente',
            'description' => null,
            'body' => null,
            'extra' => null,
            'lang' => 'it',
            'created_by' => 1,
            'modified_by' => 1,
        ],
        // 4
        [
            'object_type_id' => 2, // users
            'status' => 'on',
            'uname' => 'myz_3',
            'locked' => 0,
            'deleted' => 0,
            'created' => '2020-12-10 17:30:00',
            'modified' => '2020-12-10 17:30:00',
            'published' => null,
            'title' => 'Test Docente Universitario',
            'description' => null,
            'body' => null,
            'extra' => null,
            'lang' => 'it',
            'created_by' => 1,
            'modified_by' => 1,
        ],
        // 5
        [
            'object_type_id' => 2, // users
            'status' => 'on',
            'uname' => 'myz_4',
            'locked' => 0,
            'deleted' => 0,
            'created' => '2020-12-10 17:30:00',
            'modified' => '2020-12-10 17:30:00',
            'published' => null,
            'title' => 'Test Studente Universitario',
            'description' => null,
            'body' => null,
            'extra' => null,
            'lang' => 'it',
            'created_by' => 1,
            'modified_by' => 1,
        ],
        // 6
        [
            'object_type_id' => 3, // classrooms
            'status' => 'on',
            'uname' => 'classrooms-one',
            'locked' => 0,
            'deleted' => 0,
            'created' => '2020-12-22 12:31:00',
            'modified' => '2020-12-22 12:31:00',
            'published' => null,
            'title' => '',
            'description' => null,
            'body' => null,
            'extra' => null,
            'lang' => 'it',
            'created_by' => 2,
            'modified_by' => 2,
        ],
        // 7
        [
            'object_type_id' => 4, // messages
            'status' => 'on',
            'uname' => 'message-one',
            'locked' => 0,
            'deleted' => 0,
            'created' => '2020-12-22 12:31:00',
            'modified' => '2020-12-22 12:31:00',
            'published' => null,
            'title' => '',
            'description' => null,
            'body' => 'Ragazzi, questo è un messaggio.',
            'extra' => null,
            'lang' => 'it',
            'created_by' => 2,
            'modified_by' => 2,
        ],
    ];
}
