<?php

namespace App\Cron\Tasks;

use App\Mail\Mailer;
use App\Mail\RecipientFactory;
use App\Services\LegacyEnvironment;
use cs_environment;
use DateTimeImmutable;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class CronWorkflow implements CronTaskInterface
{
    /**
     * @var cs_environment
     */
    private cs_environment $legacyEnvironment;

    /**
     * @var RouterInterface
     */
    private RouterInterface $router;

    /**
     * @var Mailer
     */
    private Mailer $mailer;

    public function __construct(
        LegacyEnvironment $legacyEnvironment,
        RouterInterface $router,
        Mailer $mailer
    ) {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
        $this->router = $router;
        $this->mailer = $mailer;
    }

    public function run(?DateTimeImmutable $lastRun): void
    {
        $materialManager = $this->legacyEnvironment->getMaterialManager();

        $resubmissionItems = $materialManager->getResubmissionItemIDsByDate(date('Y'), date('m'), date('d'));
        foreach ($resubmissionItems as $resubmissionItemInfo) {
            $material = $materialManager->getItem($resubmissionItemInfo['item_id']);
            $latestMaterialVersionId = $materialManager->getLatestVersionID($resubmissionItemInfo['item_id']);

            if (isset($material) && !$material->isDeleted() && ($resubmissionItemInfo['version_id'] == $latestMaterialVersionId)) {
                $roomManager = $this->legacyEnvironment->getRoomManager();
                $room = $roomManager->getItem($material->getContextId());

                if ($material->getWorkflowResubmission() && $room->withWorkflowResubmission()) {
                    $emailReceivers = [];

                    if ($material->getWorkflowResubmissionWho() == 'creator') {
                        $emailReceivers[] = $material->getCreator();
                    } else {
                        $modifierList = $material->getModifierList();
                        $emailReceivers = $modifierList->to_array();
                    }

                    $to = [];
                    foreach ($emailReceivers as $emailReceiver) {
                        $to[] = $emailReceiver->getEmail();
                    }

                    $additionalReceiver = $material->getWorkflowResubmissionWhoAdditional();
                    if (!empty($additionalReceiver)) {
                        foreach (explode(',', $additionalReceiver) as $receiver) {
                            $to[] = trim($receiver);
                        }
                    }
                    $to = array_unique($to);
                    $recipients = [];
                    foreach ($to as $mail) {
                        $recipients[] = RecipientFactory::createFromRaw($mail);
                    }

                    $translator = $this->legacyEnvironment->getTranslationObject();

                    $path = $this->router->generate('app_material_detail', [
                        'roomId' => $room->getItemID(),
                        'itemId' => $material->getItemID(),
                        'versionId' => $material->getVersionID(),
                    ], UrlGeneratorInterface::ABSOLUTE_URL);

                    $link = '<a href="' . $path . '">' . $material->getTitle() . '</a>';

                    $body = $translator->getMessage('COMMON_WORKFLOW_EMAIL_BODY_RESUBMISSION', $room->getTitle(),
                        $material->getTitle(), $link);

                    $portal = $room->getPortalItem();

                    $this->mailer->sendMultipleRaw(
                        $translator->getMessage(
                            'COMMON_WORKFLOW_EMAIL_SUBJECT_RESUBMISSION',
                            $portal->getTitle()
                        ),
                        $body,
                        $recipients,
                        $portal->getTitle()
                    );

                    // change material status
                    $materialManager->setWorkflowStatus($material->getItemID(),
                        $material->getWorkflowResubmissionTrafficLight(), $material->getVersionID());
                }
            }
        }

        $validityItems = $materialManager->getValidityItemIDsByDate(date('Y'), date('m'), date('d'));
        foreach ($validityItems as $validityItemInfo) {
            $material = $materialManager->getItem($validityItemInfo['item_id']);
            $latestMaterialVersionId = $materialManager->getLatestVersionID($validityItemInfo['item_id']);

            if (isset($material) && !$material->isDeleted() && ($validityItemInfo['item_id'] == $latestMaterialVersionId)) {
                $roomManager = $this->legacyEnvironment->getRoomManager();
                $room = $roomManager->getItem($material->getContextId());

                if ($material->getWorkflowValidity() && $material->withWorkflowValidity()) {
                    $emailReceivers = [];

                    if ($material->getWorkflowValidityWho() == 'creator') {
                        $emailReceivers[] = $material->getCreator();
                    } else {
                        $modifierList = $material->getModifierList();
                        $emailReceivers = $modifierList->to_array();
                    }

                    $to = [];
                    foreach ($emailReceivers as $emailReceiver) {
                        $to[] = $emailReceiver->getEmail();
                    }

                    $additionalReceiver = $material->getWorkflowValidityWhoAdditional();
                    if (!empty($additionalReceiver)) {
                        $to = array_merge($to, explode(',', $additionalReceiver));
                    }

                    $to = array_unique($to);
                    $recipients = [];
                    foreach ($to as $mail) {
                        $recipients[] = RecipientFactory::createFromRaw($mail);
                    }

                    $translator = $this->legacyEnvironment->getTranslationObject();

                    $path = $this->router->generate('app_material_detail', [
                        'roomId' => $room->getItemID(),
                        'itemId' => $material->getItemID(),
                        'versionId' => $material->getVersionID(),
                    ], UrlGeneratorInterface::ABSOLUTE_URL);

                    $link = '<a href="' . $path . '">' . $material->getTitle() . '</a>';

                    $body = $translator->getMessage('COMMON_WORKFLOW_EMAIL_BODY_VALIDITY', $room->getTitle(),
                        $material->getTitle(), $link);

                    $portal = $room->getPortalItem();

                    $this->mailer->sendMultipleRaw(
                        $translator->getMessage(
                            'COMMON_WORKFLOW_EMAIL_SUBJECT_VALIDITY',
                            $portal->getTitle()
                        ),
                        $body,
                        $recipients,
                        $portal->getTitle()
                    );

                    // change material status
                    $materialManager->setWorkflowStatus($material->getItemID(),
                        $material->getWorkflowValidityTrafficLight(), $material->getVersionID());
                }
            }
        }
    }

    public function getSummary(): string
    {
        return 'Material workflow progression';
    }

    public function getPriority(): int
    {
        return self::PRIORITY_NORMAL;
    }
}