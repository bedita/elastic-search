<?php
declare(strict_types=1);

namespace BEdita\ElasticSearch\Model\Document;

/**
 *  Base searchable document representing a BEdita object.
 *
 * @property numeric-string $id Object ID.
 * @property string $uname Object unique name.
 * @property string $type Object type.
 * @property 'on'|'draft'|'off' $status Object status.
 * @property bool $deleted Object deletion status.
 * @property \DateTimeInterface|null $publish_start Publication start.
 * @property \DateTimeInterface|null $publish_end Publication end.
 * @property string $title Object title.
 * @property string $description Object description.
 * @property string $body Object body.
 */
class ObjectSearch extends Search
{
}
