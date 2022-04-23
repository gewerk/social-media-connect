<?php
/**
 * @link https://gewerk.dev/plugins/social-media-connect
 * @copyright 2022 gewerk, Dennis Morhardt
 * @license https://github.com/gewerk/social-media-connect/blob/main/LICENSE.md
 */

namespace Gewerk\SocialMediaConnect\Exception;

use Illuminate\Support\Collection;
use Throwable;

/**
 * This exception is thrown if a collection is constructed with an invalid item
 *
 * @package Gewerk\SocialMediaConnect\Exception
 */
class InvalidCollectionItemException extends SocialMediaConnectException
{
    /**
     * @var mixed The offending item
     */
    public $offendingItem;

    /**
     * @var Collection The affected collection
     */
    public $collection;

    /**
     * Constructs the invalid collection item exception
     *
     * @param mixed $offendingItem
     * @param Collection $collection
     * @param int $code
     * @param Throwable|null $previous
     * @return void
     */
    public function __construct($offendingItem, Collection $collection, int $code = 0, ?Throwable $previous = null)
    {
        $this->offendingItem = $offendingItem;
        $this->collection = $collection;

        parent::__construct(
            sprintf(
                'Item of type %s is not allowed in %s',
                is_object($offendingItem) ? get_class($offendingItem) : gettype($offendingItem),
                get_class($this->collection)
            ),
            $code,
            $previous
        );
    }

    /**
     * Returns the offending item
     *
     * @return mixed
     */
    public function getOffendingItem()
    {
        return $this->offendingItem;
    }

    /**
     * Returns the affected collection
     *
     * @return Collection
     */
    public function getCollection()
    {
        return $this->collection;
    }
}
