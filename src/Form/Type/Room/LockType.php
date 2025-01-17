<?php
namespace App\Form\Type\Room;

use App\Validator\Constraints\MandatoryProjectRoomAssignment;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class LockType extends AbstractType
{
    /**
     * Builds the form.
     * This method is called for each type in the hierarchy starting from the top most type.
     * Type extensions can further modify the form.
     *
     * @param  FormBuilderInterface $builder The form builder
     * @param  array                $options The options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('confirm', TextType::class, [
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\IdenticalTo([
                        'value' => mb_strtoupper($options['confirm_string']),
                        'message' => 'The input does not match {{ compared_value }}'
                    ]),
                    new MandatoryProjectRoomAssignment([
                        'room' => $options['room'],
                    ]),
                ],
                'required' => true,
                'mapped' => false,
            ])
            ->add('lock', SubmitType::class, [
                'label' => 'Confirm lock',
                'attr' => [
                    'class' => 'uk-button-danger',
                ],
            ])
        ;
    }

    /**
     * Configures the options for this type.
     *
     * @param  OptionsResolver $resolver The resolver for the options
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setRequired(['room', 'confirm_string'])
            ->setAllowedTypes('room', 'cs_room_item')
            ->setAllowedTypes('confirm_string', 'string')
            ->setDefaults([
                'room' => null,
                'translation_domain' => 'settings'
            ])
        ;
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
        return 'lock_room';
    }
}