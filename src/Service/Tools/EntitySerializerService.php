<?php

namespace App\Service\Tools;


use App\Normalizer\EntityNormalizer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class EntitySerializerService
{
    /**
     * @var EntityManagerInterface
     */
    private $em;
    /**
     * @var PropertyAccessorInterface
     */
    private $propertyAccessor;

    /**
     * EntitySerializerService constructor.
     * @param EntityManagerInterface $em
     * @param PropertyAccessorInterface $propertyAccessor
     */
    public function __construct(EntityManagerInterface $em, PropertyAccessorInterface $propertyAccessor)
    {

        $this->em = $em;
        $this->propertyAccessor = $propertyAccessor;
    }

    /**
     * @param array $params
     * @param string $entityName
     * @param array $context
     * @return object
     */
    public function deserialize(array $params, string $entityName, $context=[])
    {
        $encoders = [new JsonEncoder()];
        $normalizer = new EntityNormalizer($this->em, null, null, $this->propertyAccessor);
        if(isset($context['ignoredAttributes'])){
            $normalizer->setIgnoredAttributes($context['ignoredAttributes']);
            unset($context['ignoredAttributes']);
        }
        $normalizers = [$normalizer];
        $serializer = new Serializer($normalizers, $encoders);
        $entity = $serializer->deserialize(json_encode($params), $entityName, 'json',$context);
        return $entity;
    }
    public function serialize($entity){
        $encoders = array(new JsonEncoder());
        $normalizer = new ObjectNormalizer();

        $normalizers = [$normalizer];
        $serializer = new Serializer($normalizers,$encoders);
        $result = json_decode($serializer->serialize($entity,'json'),true);
        return $result;
    }
}