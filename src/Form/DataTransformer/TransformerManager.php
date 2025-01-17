<?php


namespace App\Form\DataTransformer;


class TransformerManager
{

    private $transformers;

    /**
     * TransformerManager constructor.
     * @param iterable $transformers
     */
    public function __construct(iterable $transformers)
    {
        $this->transformers = $transformers;
    }

    /**
     * @param $entity
     * @return DataTransformerInterface|null
     */
    public function getConverter($entity): ?DataTransformerInterface {
        /** @var DataTransformerInterface $transformer */
        foreach ($this->transformers as $transformer){
            if($transformer->supportsFormat($entity)){
                return $transformer;
            }
        }
        return null;
    }
}