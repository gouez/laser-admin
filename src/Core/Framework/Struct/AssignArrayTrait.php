<?php declare(strict_types=1);

namespace Laser\Core\Framework\Struct;

use Laser\Core\Framework\Log\Package;

#[Package('core')]
trait AssignArrayTrait
{
    /**
     * @param array<mixed> $options
     *
     * @return $this
     */
    public function assign(array $options)
    {
        foreach ($options as $key => $value) {
            if ($key === 'id' && method_exists($this, 'setId')) {
                $this->setId($value);

                continue;
            }

            try {
                $this->$key = $value;
            } catch (\Error | \Exception $error) {
                // nth
            }
        }

        return $this;
    }
}
