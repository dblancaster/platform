<?php

namespace Oro\Bundle\ApiBundle\Form\DataTransformer;

use Doctrine\ORM\ORMException;
use Oro\Bundle\ApiBundle\Form\FormUtil;
use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ApiBundle\Util\EntityLoader;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

/**
 * The base class for transformers for different kind of entity associations.
 */
abstract class AbstractEntityAssociationTransformer implements DataTransformerInterface
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var EntityLoader */
    protected $entityLoader;

    /** @var AssociationMetadata */
    protected $metadata;

    /**
     * @param DoctrineHelper      $doctrineHelper
     * @param EntityLoader        $entityLoader
     * @param AssociationMetadata $metadata
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        EntityLoader $entityLoader,
        AssociationMetadata $metadata
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->entityLoader = $entityLoader;
        $this->metadata = $metadata;
    }

    /**
     * {@inheritdoc}
     */
    public function transform($value)
    {
        return null;
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function reverseTransform($value)
    {
        if (null === $value || '' === $value) {
            return null;
        }
        if (!\is_array($value)) {
            throw new TransformationFailedException('Expected an array.');
        }
        if (empty($value)) {
            return null;
        }

        if (empty($value['class'])) {
            throw FormUtil::createTransformationFailedException(
                'Expected an array with "class" element.',
                'oro.api.form.invalid_entity_type'
            );
        }

        $entityClass = $value['class'];
        $acceptableClassNames = $this->metadata->getAcceptableTargetClassNames();
        if (empty($acceptableClassNames)) {
            if (!$this->metadata->isEmptyAcceptableTargetsAllowed()) {
                throw FormUtil::createTransformationFailedException(
                    'There are no acceptable classes.',
                    'oro.api.form.no_acceptable_entities'
                );
            }
        } elseif (!\in_array($entityClass, $acceptableClassNames, true)) {
            throw FormUtil::createTransformationFailedException(
                sprintf(
                    'The "%s" class is not acceptable. Acceptable classes: %s.',
                    $entityClass,
                    implode(',', $acceptableClassNames)
                ),
                'oro.api.form.not_acceptable_entity'
            );
        }

        if (!\array_key_exists('id', $value)) {
            throw new TransformationFailedException('Expected an array with "id" element.');
        }

        if (!$this->isEntityIdAcceptable($value['id'])) {
            throw FormUtil::createTransformationFailedException(
                'The "id" element is expected to be an integer, non-empty string or non-empty array.',
                'oro.api.form.invalid_entity_id'
            );
        }

        return $this->getEntity($entityClass, $value['id']);
    }

    /**
     * @param mixed $entityId
     * @return bool
     */
    protected function isEntityIdAcceptable($entityId): bool
    {
        return \is_int($entityId)
            || (\is_string($entityId) && '' !== trim($entityId))
            || (\is_array($entityId) && \count($entityId));
    }

    /**
     * @param string $entityClass
     * @param mixed  $entityId
     *
     * @return object
     */
    abstract protected function getEntity($entityClass, $entityId);

    /**
     * @param string $entityClass
     * @param mixed  $entityId
     *
     * @return object
     */
    protected function loadEntity($entityClass, $entityId)
    {
        if (!$this->doctrineHelper->isManageableEntityClass($entityClass)) {
            throw FormUtil::createTransformationFailedException(
                sprintf('The "%s" class must be a managed Doctrine entity.', $entityClass),
                'oro.api.form.not_manageable_entity'
            );
        }
        $entity = null;
        try {
            $entity = $this->entityLoader->findEntity($entityClass, $entityId, $this->metadata->getTargetMetadata());
        } catch (ORMException $e) {
            throw new TransformationFailedException(
                sprintf(
                    'An "%s" entity with "%s" identifier cannot be loaded.',
                    $entityClass,
                    $this->humanizeEntityId($entityId)
                ),
                0,
                $e,
                'oro.api.form.load_entity_failed'
            );
        }
        if (null === $entity) {
            throw FormUtil::createTransformationFailedException(
                sprintf(
                    'An "%s" entity with "%s" identifier does not exist.',
                    $entityClass,
                    $this->humanizeEntityId($entityId)
                ),
                'oro.api.form.entity_does_not_exist'
            );
        }

        return $entity;
    }

    /**
     * @param string $entityClass
     *
     * @return string
     */
    protected function resolveEntityClass($entityClass)
    {
        $resolvedEntityClass = $this->doctrineHelper->resolveManageableEntityClass($entityClass);

        return $resolvedEntityClass ?? $entityClass;
    }

    /**
     * @param mixed $entityId
     *
     * @return string
     */
    protected function humanizeEntityId($entityId)
    {
        if (\is_array($entityId)) {
            $elements = [];
            foreach ($entityId as $fieldName => $fieldValue) {
                $elements[] = sprintf('%s = %s', $fieldName, $fieldValue);
            }

            return sprintf('array(%s)', implode(', ', $elements));
        }

        return (string)$entityId;
    }
}
