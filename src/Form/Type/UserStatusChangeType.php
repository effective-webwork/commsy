<?php
namespace App\Form\Type;

use App\Validator\Constraints\UniqueModeratorConstraint;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class UserStatusChangeType extends AbstractType
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
        $formData = $builder->getData();

        $builder
            ->add('inform_user', ChoiceType::class, [
                'label' => 'Inform user',
                'choices' => [
                    'Yes' => true,
                    'No' => false,
                ],
                'expanded' => true,
                'translation_domain' => 'user',
                'choice_translation_domain' => 'form',
                'required' => true,
                'data' => true,
            ])
            ->add('userIds', CollectionType::class, [
                'entry_type' => HiddenType::class,
                'label' => false,
                'constraints' => [
                    new UniqueModeratorConstraint([
                        'concernsOwnRoomMembership' => false,
                        'newUserStatus' => $formData['status'],
                        'userIds' => $formData['userIds'],
                    ]),
                ],
                'required' => true,
                'allow_add' => true,
            ])
            ->add('status', HiddenType::class, [
                'label' => false,
                'required' => true,
            ])
            ->add('save', SubmitType::class, [
                'attr' => [
                    'class' => 'uk-button-primary',
                ],
                'label' => 'Change status',
                'translation_domain' => 'user',
            ])
            ->add('cancel', SubmitType::class, [
                'attr' => [
                    'formnovalidate' => 'formnovalidate',
                ],
                'label' => 'cancel',
                'translation_domain' => 'form',
                'validation_groups' => false,
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
        return 'user_status';
    }
}