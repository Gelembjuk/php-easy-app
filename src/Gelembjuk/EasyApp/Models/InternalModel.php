<?php

namespace Gelembjuk\EasyApp\Models;

/**
 * Base class for internal models.
 * 
 * Provides functionality for hydrating models from arrays and
 * converting models to arrays, including support for nested models,
 * DateTime objects, and Enums.
 */

abstract class InternalModel
{
    /* ----------------------------
     * Public API
     * ---------------------------- */

    /**
     * Convert the model instance to an array representation.
     * 
     * Recursively converts all nested objects, arrays, DateTimes, and Enums
     * to their primitive representations. Handles circular references.
     * 
     * @return array The array representation of the model
     */
    final public function toArray(): array
    {
        return self::normalize($this, new \SplObjectStorage());
    }

    /**
     * Create a new model instance from an array of data.
     * 
     * Creates a new instance of the model and hydrates it with the provided data.
     * Automatically handles nested models, DateTimes, Enums, and arrays.
     * 
     * @param array $data The data to hydrate the model with
     * @return static The hydrated model instance
     */
    final public static function fromArray(array $data): static
    {
        $object = new static();
        $object->hydrate($data);
        return $object;
    }

    /**
     * Copy property values from another model instance.
     * 
     * Performs a deep copy of all properties that have the same name and are public
     * in both the source model and the current model instance. Recursively copies
     * nested InternalModel instances and arrays containing InternalModel instances.
     * 
     * @param InternalModel $sourceModel The source model to copy values from
     * @return void
     */
    public function copyFrom(InternalModel $sourceModel): void
    {
        $sourceReflection = new \ReflectionClass($sourceModel);
        $destinationReflection = new \ReflectionClass($this);

        foreach ($sourceReflection->getProperties(\ReflectionProperty::IS_PUBLIC) as $sourceProperty) {
            $propertyName = $sourceProperty->getName();

            // Check if destination has the same public property
            if (!$destinationReflection->hasProperty($propertyName)) {
                continue;
            }

            $destinationProperty = $destinationReflection->getProperty($propertyName);

            if (!$destinationProperty->isPublic()) {
                continue;
            }

            // Skip uninitialized properties
            if (!$sourceProperty->isInitialized($sourceModel)) {
                continue;
            }

            // Deep copy the value
            $this->$propertyName = $this->deepCopyValue($sourceModel->$propertyName);
        }
    }

    /**
     * Create a new instance and copy property values from another model instance.
     * 
     * Creates a new instance of the current class and performs a deep copy of all
     * properties that have the same name and are public in both models.
     * 
     * @param InternalModel $sourceModel The source model to copy values from
     * @return static The new instance with copied values
     */
    public static function cloneFrom(InternalModel $sourceModel): static
    {
        $newInstance = new static();
        $newInstance->copyFrom($sourceModel);
        return $newInstance;
    }

    /**
     * Perform a deep copy of a value.
     * 
     * Recursively copies InternalModel instances and arrays containing InternalModel instances.
     * Other values are returned as-is (scalar values, or objects that are not InternalModel).
     * 
     * @param mixed $value The value to copy
     * @return mixed The copied value
     */
    protected function deepCopyValue(mixed $value): mixed
    {
        // Handle InternalModel instances
        if ($value instanceof InternalModel) {
            $copiedModel = new ($value::class)();
            $copiedModel->copyFrom($value);
            return $copiedModel;
        }

        // Handle arrays (may contain InternalModel instances)
        if (is_array($value)) {
            $result = [];
            foreach ($value as $key => $item) {
                $result[$key] = $this->deepCopyValue($item);
            }
            return $result;
        }

        // For all other types (scalars, other objects), return as-is
        return $value;
    }

    /* ----------------------------
     * Hydration (array → object)
     * ---------------------------- */

    /**
     * Hydrate the model with data from an array.
     * 
     * Iterates through the provided data and sets each property that exists
     * in the model. Uses denormalization to convert array values to appropriate types.
     * 
     * @param array $data The data to populate the model with
     * @return void
     */
    protected function hydrate(array $data): void
    {
        foreach ($data as $property => $value) {
            if (!property_exists($this, $property)) {
                continue;
            }

            $this->$property = $this->denormalizeProperty($property, $value);
        }
    }

    /**
     * Denormalize a property value from its array representation to the appropriate type.
     * 
     * Handles conversion of:
     * - Nested InternalModel instances (from arrays)
     * - DateTime instances (from strings)
     * - Enum instances (from scalar values)
     * - Arrays with nested models (using ArrayOf attribute)
     * 
     * @param string $property The name of the property being denormalized
     * @param mixed $value The value to denormalize
     * @return mixed The denormalized value
     */
    protected function denormalizeProperty(string $property, mixed $value): mixed
    {
        $rp = new \ReflectionProperty($this, $property);
        $type = $rp->getType();

        // null handling
        if ($value === null) {
            return null;
        }

        if ($type instanceof \ReflectionNamedType) {
            $typeName = $type->getName();

            // Nested InternalModel
            if (
                is_subclass_of($typeName, self::class) &&
                is_array($value)
            ) {
                return $typeName::fromArray($value);
            }

            // DateTime
            if ($typeName === \DateTimeInterface::class || is_subclass_of($typeName, \DateTimeInterface::class)) {
                return new \DateTimeImmutable($value);
            }

            // Enum
            if (enum_exists($typeName)) {
                return $typeName::from($value);
            }

            // Array (possibly array of models)
            if ($typeName === 'array' && is_array($value)) {
                return $this->denormalizeArray($rp, $value);
            }
        }

        return $value;
    }

    /**
     * Denormalize an array property that may contain nested model instances.
     * 
     * If the property has an ArrayOf attribute specifying a model class,
     * each array element will be converted to an instance of that model.
     * 
     * @param \ReflectionProperty $rp The reflection property instance
     * @param array $value The array value to denormalize
     * @return array The denormalized array with model instances
     */
    protected function denormalizeArray(\ReflectionProperty $rp, array $value): array
    {
        $attrs = $rp->getAttributes(ArrayOf::class);

        if (!$attrs) {
            return $value;
        }

        $class = $attrs[0]->newInstance()->class;

        if (!is_subclass_of($class, self::class)) {
            return $value;
        }

        return array_map(
            fn ($item) => is_array($item)
                ? $class::fromArray($item)
                : $item,
            $value
        );
    }

    /* ----------------------------
     * Normalization (object → array)
     * ---------------------------- */

    /**
     * Normalize a value to its array representation.
     * 
     * Recursively converts complex types to their primitive representations:
     * - InternalModel instances → arrays of properties
     * - DateTime instances → ISO 8601 strings
     * - Enum instances → their value or name
     * - Arrays → recursively normalized arrays
     * 
     * Tracks visited objects to prevent infinite loops from circular references.
     * 
     * @param mixed $value The value to normalize
     * @param \SplObjectStorage $seen Storage for tracking visited objects
     * @return mixed The normalized value
     */
    protected static function normalize(mixed $value, \SplObjectStorage $seen): mixed
    {
        if (is_object($value)) {
            if (isset($seen[$value])) {
                return null;
            }

            $seen[$value] = true;

            // InternalModel
            if ($value instanceof self) {
                return self::normalize(get_object_vars($value), $seen);
            }

            // DateTime
            if ($value instanceof \DateTimeInterface) {
                return $value->format(DATE_ATOM);
            }

            // Enum
            if ($value instanceof \UnitEnum) {
                return $value->value ?? $value->name;
            }

            return $value;
        }

        if (is_array($value)) {
            $result = [];
            foreach ($value as $k => $v) {
                $result[$k] = self::normalize($v, $seen);
            }
            return $result;
        }

        return $value;
    }
}
