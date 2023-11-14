<?php
declare(strict_types=1);

namespace BEdita\ElasticSearch\Model\Document;

use Cake\ElasticSearch\Document;

/**
 * Base searchable document.
 *
 * @property int|string $id Document ID.
 */
class Search extends Document
{
    /**
     * Returns the score of this document as returned by ElasticSearch after search.
     *
     * @return float|null
     */
    public function score(): float|null
    {
        if ($this->_result) {
            return $this->_result->getScore();
        }

        return null;
    }
}
