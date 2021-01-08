<?php
namespace App\Filter;

use App\Entity\SavedSearch;
use App\Form\Type\Custom\Select2ChoiceType;
use App\Model\SearchData;
use App\Search\SearchManager;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ChoiceList\Loader\CallbackChoiceLoader;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type as Types;
use Symfony\Component\Validator\Constraints;

use Lexik\Bundle\FormFilterBundle\Filter\Form\Type as Filters;
use Symfony\Component\Translation\TranslatorInterface;

class SearchFilterType extends AbstractType
{
    /**
     * @var TranslatorInterface $translator
     */
    private $translator;

    private $searchManager;

    public function __construct(TranslatorInterface $translator, SearchManager $searchManager)
    {
        $this->translator = $translator;
        $this->searchManager = $searchManager;
    }

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
        /** @var SearchData $searchData */
        $searchData = $builder->getData();

        $builder
            ->add('selectedSavedSearch', EntityType::class, [
                'attr' => [
// TODO: ideally, don't reload if the "Neue Ansicht" gets chosen again
                    'onchange' => 'this.form.submit()',
                ],
                'class' => SavedSearch::class,
                'choices' => $searchData->getSavedSearches(),
                'choice_label' => 'title',
                'label' => 'My view',
                'required' => false,
                'placeholder' => 'New view',
            ])
//            ->add('selectedSavedSearchId', Types\ChoiceType::class, [
//                'attr' => [
//// TODO: ideally, don't reload if the "Neue Ansicht" gets chosen again
//                    'onchange' => 'this.form.submit()',
//                ],
//                'choice_loader' => new CallbackChoiceLoader(function() use ($searchData) {
//                    $translatedTitleNew = $this->translator->trans('New view', [], 'search');
//                    return array_merge([$translatedTitleNew => 0], $this->buildSavedSearchChoices($searchData->getSavedSearches()));
//                }),
//                'label' => 'My view',
//                'required' => false,
//                'placeholder' => false,
//            ])
            ->add('selectedSavedSearchTitle', Types\TextType::class, [
// TODO: only require a non-empty title (which does not only consist of whitespace) if the Save button was clicked
//                'constraints' => [
//                    new Constraints\NotBlank([
//                        'normalizer' => 'trim',
//                    ]),
//                ],
                'label' => 'Title',
//                'required' => true,

            ])
            ->add('save', Types\SubmitType::class, [
                'attr' => [
                    'class' => 'uk-button-primary',
                ],
                'label' => 'save',
                'translation_domain' => 'form',
            ])
            /**
             * Since this form uses the same data class as the global search form, it is important to keep the field
             * name of the search query phrase identical
             */
            ->add('phrase', Types\HiddenType::class, [
                'label' => false,
            ])
            ->add('all_rooms', Filters\CheckboxFilterType::class, [
                'attr' => [
                    'onchange' => 'this.form.submit()',
                ],
                'label' => 'Search in all my rooms',
                'required' => false,
                'label_attr' => [
                    'class' => 'uk-form-label',
                ],
            ])
            ->add('appears_in', Filters\ChoiceFilterType::class, [
                'choice_attr' => function($choice, $key, $value) {
                    return [
                        'onchange' => 'this.form.submit()',
                    ];
                },
                'choices' => [
                    'Title' => 'title',
                    'Description' => 'description',
//                    'Files' => 'files',
                ],
                'label' => 'Appearing in',
                'required' => false,
                'expanded' => true,
                'multiple' => true,
                'placeholder' => false,
            ])
            ->add('selectedCreator', Select2ChoiceType::class, [
                'attr' => [
                    'onchange' => 'this.form.submit()',
                ],
                'choice_loader' => new CallbackChoiceLoader(function() use ($searchData) {
                    $translatedTitleAny = $this->translator->trans('any', [], 'form');
                    return array_merge([$translatedTitleAny => 'all'], $this->buildTermChoices($searchData->getCreators()));
                }),
                'label' => 'Creator',
                'required' => false,
            ])
            ->add('creation_date_range', Filters\DateRangeFilterType::class, [
                'attr' => [
                    'onchange' => 'this.form.submit()',
                    'data-uk-datepicker' => '{format:\'DD.MM.YYYY\'}',
                    'autocomplete' => 'off',
                ],
                'label' => 'Created from/until',
                'required' => false,
                // NOTE: while the left/right date labels won't display, specifying them helps with proper formatting
                'left_date_options' => [
                    'label'  => 'from',
                    'input'  => 'datetime',
                    'widget' => 'single_text',
                    'format' => 'dd.MM.yyyy',
                ],
                'right_date_options' => [
                    'label'  => 'until',
                    'input'  => 'datetime',
                    'widget' => 'single_text',
                    'format' => 'dd.MM.yyyy',
                ],
            ])
            ->add('modification_date_range', Filters\DateRangeFilterType::class, [
                'attr' => [
                    'onchange' => 'this.form.submit()',
                    'data-uk-datepicker' => '{format:\'DD.MM.YYYY\'}',
                    'autocomplete' => 'off',
                ],
                'label' => 'Last modified from/until',
                'required' => false,
                // NOTE: while the left/right date labels won't display, specifying them helps with proper formatting
                'left_date_options' => [
                    'label'  => 'from',
                    'input'  => 'datetime',
                    'widget' => 'single_text',
                    'format' => 'dd.MM.yyyy',
                ],
                'right_date_options' => [
                    'label'  => 'until',
                    'input'  => 'datetime',
                    'widget' => 'single_text',
                    'format' => 'dd.MM.yyyy',
                ],
            ])
            ->add('selectedRubric', Types\ChoiceType::class, [
                'attr' => [
                    'onchange' => 'this.form.submit()',
                ],
                'choice_loader' => new CallbackChoiceLoader(function() use ($searchData) {
                    $translatedTitleAny = $this->translator->trans('any', [], 'form');
                    return array_merge([$translatedTitleAny => 'all'], $this->buildRubricsChoices($searchData->getRubrics()));
                }),
                'label' => 'Rubric',
                'required' => false,
                'placeholder' => false,
            ])
            ->add('selectedHashtags', Select2ChoiceType::class, [
                'attr' => [
                    'onchange' => 'this.form.submit()',
                ],
                'choice_loader' => new CallbackChoiceLoader(function() use ($searchData) {
                    return $this->buildTermChoices($searchData->getHashtags());
                }),
                'label' => 'Hashtags',
                'expanded' => false,
                'multiple' => true,
                'required' => false,
            ])
            ->add('selectedCategories', Select2ChoiceType::class, [
                'attr' => [
                    'onchange' => 'this.form.submit()',
                ],
                'choice_loader' => new CallbackChoiceLoader(function() use ($searchData) {
                    return $this->buildTermChoices($searchData->getCategories());
                }),
                'label' => 'Categories',
                'expanded' => false,
                'multiple' => true,
                'required' => false,
            ]);
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
        return 'search_filter';
    }

    /**
     * Configures the options for this type.
     * 
     * @param  OptionsResolver $resolver The resolver for the options
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setRequired(['contextId'])
            ->setDefaults([
                'csrf_protection'    => false,
                'validation_groups'  => array('filtering'), // avoid NotBlank() constraint-related message
                'method'             => 'get',
                'translation_domain' => 'search',
            ]);
    }

    /**
     * Builds the array of choices for the rubric filter field.
     *
     * @param array|null $rubrics associative array of rubrics (key: rubric name, value: count)
     */
    private function buildRubricsChoices($rubrics): array
    {
        if (!isset($rubrics) || empty($rubrics)) {
            return [];
        }

        $choices = [];
        foreach ($rubrics as $name => $count) {
            $translatedTitle = $this->translator->transChoice(ucfirst($name), 1, [], 'rubric');
            if ($name === "label") {
                $translatedTitle = $this->translator->trans("Groups, Topics and Institutions", [], 'search');
            }
            $rubric = $translatedTitle . " (" . $count . ")";
            $choices[$rubric] = $name;
        }

        return $choices;
    }

    /**
     * Builds the array of choices for the creators/hashtags/categories filter fields.
     *
     * @param array|null $terms associative array of creator/hashtag/category terms (key: term name, value: count)
     */
    private function buildTermChoices($terms): array
    {
        if (!isset($terms) || empty($terms)) {
            return [];
        }

        $choices = [];
        foreach ($terms as $name => $count) {
            $term = $name . " (" . $count . ")";
            $choices[$term] = $name;
        }

        return $choices;
    }

    /**
     * Builds the array of choices for the dropdown of saved searches (aka "views").
     *
     * @param SavedSearch[]|null $savedSearches array of SavedSearch objects
     */
    private function buildSavedSearchChoices(?array $savedSearches): array
    {
        if (empty($savedSearches)) {
            return [];
        }

        $choices = [];
        foreach ($savedSearches as $savedSearch) {
            $choices[$savedSearch->getTitle()] = $savedSearch->getId();
        }

        return $choices;
    }
}
