<?php

namespace App\Form\Type\Portal;

use App\Entity\License;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type as Types;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LicenseSortType extends AbstractType
{
    /**
     * Builds the form.
     *
     * @param FormBuilderInterface $builder The form builder
     * @param array $options The options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('license', EntityType::class, [
                'class' => License::class,
                'query_builder' => function (EntityRepository $er) use ($options) {
                    return $er->createQueryBuilder('l')
                        ->where('l.contextId = :contextId')
                        ->orderBy('l.position')
                        ->setParameter('contextId', $options['portalId']);
                },
                'choice_label' => 'title',
                'multiple' => true,
                'expanded' => true,
                'label' => false,
            ])
            ->add('structure', Types\HiddenType::class, [
            ])
            ->add('update', Types\SubmitType::class, [
                'label' => 'save',
                'translation_domain' => 'form',
            ]);
    }

    /**
     * Configures the options for this type.
     *
     * @param OptionsResolver $resolver The resolver for the options
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setRequired(['portalId'])
            ->setDefaults(['translation_domain' => 'portal']);
    }

    /**
     * Returns the prefix of the template block name for this type.
     * The block prefix defaults to the underscored short class name with the "Type" suffix removed
     * (e.g. "UserProfileType" => "user_profile").
     *
     * @return string The prefix of the template block name
     */
    public function getBlockPrefix()
    {
        return 'license_sort';
    }
}
