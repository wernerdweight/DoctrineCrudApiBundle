<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Service\Response;

use Symfony\Component\DependencyInjection\Container;
use WernerDweight\DoctrineCrudApiBundle\Exception\FormatterException;
use WernerDweight\RA\RA;
use WernerDweight\Stringy\Stringy;

class PayloadResolver
{
    private Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function resolve(RA $payload): RA
    {
        return $payload->map(function (string $item): mixed {
            $stringyItem = new Stringy($item);
            if (0 === $stringyItem->getPositionOfSubstring('@')) {
                $stringyItem->substring(1);
                $parts = new RA($stringyItem->explode('.'));
                $service = $this->container->get((string)$parts->shift());
                return $this->resolvePayloadFromService($service, $parts);
            }
            return $item;
        });
    }

    private function resolvePayloadFromService(mixed $item, RA $parts): mixed
    {
        $parts->walk(function (string $field) use (&$item): void {
            $propertyName = ucfirst($field);
            if (true === method_exists($item, 'get' . $propertyName)) {
                $item = $item->{'get' . $propertyName}();
                return;
            }
            if (true === method_exists($item, 'is' . $propertyName)) {
                $item = $item->{'is' . $propertyName}();
                return;
            }
            if (true === method_exists($item, $field)) {
                $item = $item->{$field}();
                return;
            }
            if (true === property_exists($item, $field)) {
                $item = $item->{$field};
                return;
            }
            if (true === method_exists($item, 'get')) {
                $item = $item->get($field);
                return;
            }
            throw throw new FormatterException(FormatterException::EXCEPTION_NO_PROPERTY_GETTER, [
                $field,
                get_class($item),
            ]);
        });
        return $item;
    }
}
