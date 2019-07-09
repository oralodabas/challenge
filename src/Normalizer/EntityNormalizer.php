<?php

namespace App\Normalizer;


use App\Exception\ObjectNotFoundException;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use LogicException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyInfo\PropertyTypeExtractorInterface;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

/**
 * Entity normalizer
 * @package App\Normalizer
 */
class EntityNormalizer extends ObjectNormalizer
{
    protected $em;
    /**
     * @var null|PropertyTypeExtractorInterface
     */
    private $propertyTypeExtractor;
    private $requestData = null;

    public function __construct(
        EntityManagerInterface $em,
        ?ClassMetadataFactoryInterface $classMetadataFactory = null,
        ?NameConverterInterface $nameConverter = null,
        PropertyAccessorInterface $propertyAccessor,
        ?PropertyTypeExtractorInterface $propertyTypeExtractor = null
    )
    {
        parent::__construct($classMetadataFactory, $nameConverter, $propertyAccessor, $propertyTypeExtractor);

        // Entity manager
        $this->em = $em;
        $this->classMetadataFactory = $classMetadataFactory;
        $this->nameConverter = $nameConverter;
        $this->propertyAccessor = $propertyAccessor;
        $this->propertyTypeExtractor = $propertyTypeExtractor;
    }

    public function supportsDenormalization($data, $type, $format = null)
    {
        return strpos($type, 'App\\Entity\\') === 0;
    }

    /**
     * @inheritDoc
     */
    public function denormalize($data, $class, $format = null, array $context = [])
    {
        $this->requestData = $data;
        if (!isset($context['cache_key'])) {
            $context['cache_key'] = $this->getCacheKey($format, $context);
        }

        $allowedAttributes = $this->getAllowedAttributes($class, $context, true);
        $normalizedData = $this->prepareForDenormalization($data);
        $extraAttributes = [];

        $reflectionClass = new \ReflectionClass($class);
        $object = $this->instantiateObject($normalizedData, $class, $context, $reflectionClass, $allowedAttributes, $format);

        foreach ($normalizedData as $attribute => $value) {
            if ($this->nameConverter) {
                $attribute = $this->nameConverter->denormalize($attribute);
            }

            if ((false !== $allowedAttributes && !in_array($attribute, $allowedAttributes)) || !$this->isAllowedAttribute($class, $attribute, $format, $context)) {
                if (isset($context[self::ALLOW_EXTRA_ATTRIBUTES]) && !$context[self::ALLOW_EXTRA_ATTRIBUTES]) {
                    $extraAttributes[] = $attribute;
                }

                continue;
            }

            $value = $this->validateAndDenormalize($class, $attribute, $value, $format, $context);
            try {
                $this->setAttributeValue($object, $attribute, $value, $format, $context);
            } catch (InvalidArgumentException $e) {
                throw new NotNormalizableValueException($e->getMessage(), $e->getCode(), $e);
            }
        }

        if (!empty($extraAttributes)) {
            throw new ExtraAttributesException($extraAttributes);
        }

        return $object;

        //return $this->em->find($class, $data);
    }

    private function validateAndDenormalize(string $currentClass, string $attribute, $data, ?string $format, array $context)
    {

        $metadata = $this->em->getClassMetadata($currentClass);
        $associations = $metadata->getAssociationMappings();
        $nameConverter = new CamelCaseToSnakeCaseNameConverter();
        $camelCasedAttribute = $nameConverter->denormalize($attribute);

        if (is_array($this->requestData[$attribute])) {
            foreach ($this->requestData[$attribute] as $item) {
                if (!is_array($item)) continue;
                $persistedEntity = $this->denormalize($item, $associations[$camelCasedAttribute]['targetEntity']);
                /*$this->em->persist($persistedEntity);
                $this->em->flush();*/
                $this->requestData[$attribute][] = $persistedEntity;
            }


        }
        $dateTypes = [
            Type::TIME => \DateTime::class,
            Type::TIME_IMMUTABLE => \DateTime::class,
            Type::DATE => \DateTime::class,
            Type::DATETIME => \DateTime::class,
            Type::DATETIMETZ => \DateTimeZone::class,
            Type::DATE_IMMUTABLE => \DateTimeImmutable::class,
            Type::DATEINTERVAL => \DateInterval::class,
            Type::DATETIME_IMMUTABLE => \DateTimeImmutable::class,
            Type::DATETIMETZ_IMMUTABLE => \DateTimeImmutable::class,
        ];
        if (isset($associations[$camelCasedAttribute])) {
            $association = $associations[$camelCasedAttribute];

            $entityName = $association['targetEntity'];
            $fieldName = $nameConverter->normalize($association['fieldName']);
            $value = null;
            if (isset($this->requestData[$fieldName])) {

                if ($association['type'] == ClassMetadataInfo::MANY_TO_ONE || $association['type'] == ClassMetadataInfo::ONE_TO_ONE) {
                    //if relation is ManyToOne then value must be object of entity, so we should use find
                    $value = $this->em->getRepository($entityName)->find($data);
                    if (is_null($value) || empty($value))
                        throw new ObjectNotFoundException(sprintf('Related data is not found that is specified with param name: %s ', $camelCasedAttribute));
                } else {
                    $value = $this->em->getRepository($entityName)->findBy(['id' => $data]);
                    if (is_null($value) || empty($value) || count($data) != count($value))
                        throw new ObjectNotFoundException(sprintf('Related data is not found that is specified with param name: %s ', $camelCasedAttribute));
                }
            }

            return $value;
        } else if (isset($metadata->fieldMappings[$camelCasedAttribute])) {
            if ($metadata->fieldMappings[$camelCasedAttribute]['nullable'] === true && ($data === null || $data == null)) {
                return $data;
            }
            if (isset($dateTypes[$metadata->getTypeOfField($camelCasedAttribute)])) {
                $dateTimeNormalizer = new DateTimeNormalizer();
                return $dateTimeNormalizer->denormalize($data, $dateTypes[$metadata->getTypeOfField($camelCasedAttribute)]);
            }
        }

        if (null === $this->propertyTypeExtractor || null === $types = $this->propertyTypeExtractor->getTypes($currentClass, $attribute)) {
            return $data;
        }

        $expectedTypes = [];
        foreach ($types as $type) {
            if (null === $data && $type->isNullable()) {
                return;
            }

            if ($type->isCollection() && null !== ($collectionValueType = $type->getCollectionValueType()) && Type::BUILTIN_TYPE_OBJECT === $collectionValueType->getBuiltinType()) {
                $builtinType = Type::BUILTIN_TYPE_OBJECT;
                $class = $collectionValueType->getClassName() . '[]';

                if (null !== $collectionKeyType = $type->getCollectionKeyType()) {
                    $context['key_type'] = $collectionKeyType;
                }
            } else {
                $builtinType = $type->getBuiltinType();
                $class = $type->getClassName();
            }

            $expectedTypes[Type::BUILTIN_TYPE_OBJECT === $builtinType && $class ? $class : $builtinType] = true;

            if (Type::BUILTIN_TYPE_OBJECT === $builtinType) {
                if (!$this->serializer instanceof DenormalizerInterface) {
                    throw new LogicException(sprintf('Cannot denormalize attribute "%s" for class "%s" because injected serializer is not a denormalizer', $attribute, $class));
                }

                $childContext = $this->createChildContext($context, $attribute);
                if ($this->serializer->supportsDenormalization($data, $class, $format, $childContext)) {
                    return $this->serializer->denormalize($data, $class, $format, $childContext);
                }
            }

            // JSON only has a Number type corresponding to both int and float PHP types.
            // PHP's json_encode, JavaScript's JSON.stringify, Go's json.Marshal as well as most other JSON encoders convert
            // floating-point numbers like 12.0 to 12 (the decimal part is dropped when possible).
            // PHP's json_decode automatically converts Numbers without a decimal part to integers.
            // To circumvent this behavior, integers are converted to floats when denormalizing JSON based formats and when
            // a float is expected.
            if (Type::BUILTIN_TYPE_FLOAT === $builtinType && is_int($data) && false !== strpos($format, JsonEncoder::FORMAT)) {
                return (float)$data;
            }

            if (call_user_func('is_' . $builtinType, $data)) {
                return $data;
            }
        }

        if (!empty($context[self::DISABLE_TYPE_ENFORCEMENT])) {
            return $data;
        }

        throw new NotNormalizableValueException(sprintf('The type of the "%s" attribute for class "%s" must be one of "%s" ("%s" given).', $attribute, $currentClass, implode('", "', array_keys($expectedTypes)), gettype($data)));
    }

    /**
     * Gets the cache key to use.
     *
     * @return bool|string
     */
    private function getCacheKey(?string $format, array $context)
    {
        try {
            return md5($format . serialize($context));
        } catch (\Exception $exception) {
            // The context cannot be serialized, skip the cache
            return false;
        }
    }

    protected function setAttributeValue($object, $attribute, $value, $format = null, array $context = [])
    {
        try {
            $this->propertyAccessor->setValue($object, $attribute, $value);
        } catch (NoSuchPropertyException $exception) {
            // Properties not found are ignored
        }
    }

    /**
     * Camelizes a given string.
     */
    private function camelize(string $string): string
    {
        return str_replace(' ', '', ucwords(str_replace('_', ' ', $string)));
    }
}