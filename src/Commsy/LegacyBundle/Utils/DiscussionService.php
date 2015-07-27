<?php
namespace Commsy\LegacyBundle\Utils;

use Symfony\Component\Form\Form;

use Commsy\LegacyBundle\Services\LegacyEnvironment;

class DiscussionService
{
    private $legacyEnvironment;

    private $discussionManager;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();

        $this->discussionManager = $this->legacyEnvironment->getDiscussionManager();
        $this->discussionManager->reset();
    }

    public function getListDiscussions($roomId, $max, $start)
    {
        $this->discussionManager->reset();
        $this->discussionManager->setContextLimit($roomId);
        $this->discussionManager->setIntervalLimit($start, $max);

        $this->discussionManager->select();
        $discussionList = $this->discussionManager->get();

        return $discussionList->to_array();
    }

    public function setFilterConditions(Form $filterForm)
    {
        $formData = $filterForm->getData();

        // activated
        if ($formData['activated']) {
            $this->discussionManager->showNoNotActivatedEntries();
        }

        // rubrics
        if ($formData['rubrics']) {
            // group
            if (isset($formData['rubrics']['group'])) {
                $relatedLabel = $formData['rubrics']['group'];
                $this->discussionManager->setGroupLimit($relatedLabel->getItemId());
            }
            
            // topic
            if (isset($formData['rubrics']['topic'])) {
                $relatedLabel = $formData['rubrics']['topic'];
                $this->discussionManager->setTopicLimit($relatedLabel->getItemId());
            }
            
            // institution
            if (isset($formData['rubrics']['institution'])) {
                $relatedLabel = $formData['rubrics']['institution'];
                $this->discussionManager->setInstitutionLimit($relatedLabel->getItemId());
            }
        }
    }
    
    public function getDiscussion($itemId)
    {
        return $this->discussionManager->getItem($itemId);
    }
}