<?php
namespace CampaignChain\Activity\SocialMediaBundle\Controller;

use CampaignChain\CoreBundle\Controller\Module\AbstractActivityHandler;
use CampaignChain\Operation\SocialMediaBundle\Job\SocialMediaSchedule;
use CampaignChain\Operation\SocialMediaBundle\EntityService\SocialMediaSchedule as SocialMediaScheduleService;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Form\Form;
use CampaignChain\CoreBundle\Entity\Location;
use CampaignChain\CoreBundle\Entity\Campaign;
use CampaignChain\CoreBundle\Entity\Activity;
use CampaignChain\CoreBundle\Entity\Operation;
use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Bundle\TwigBundle\TwigEngine;
use Symfony\Component\HttpFoundation\Session\Session;
use CampaignChain\CoreBundle\Util\SchedulerUtil;

/**
 * Class SocialMediaScheduleHandler
 * @package CampaignChain\Activity\SocialMediaBundle\Controller\Module
 */
class SocialMediaScheduleHandler extends AbstractActivityHandler
{
    protected $em;
    protected $session;
    protected $templating;
    protected $contentService;
    protected $job;

    /** @var SchedulerUtil */
    protected $schedulerUtil;

    public function __construct(
        ManagerRegistry $managerRegistry,
        Session $session,
        TwigEngine $templating,
        SocialMediaScheduleService $contentService,
        SocialMediaSchedule $job,
        SchedulerUtil $schedulerUtil
    )
    {
        $this->em = $managerRegistry->getManager();
        $this->session = $session;
        $this->templating = $templating;
        $this->contentService = $contentService;
        $this->job = $job;
        $this->schedulerUtil = $schedulerUtil;
    }

    /**
     * When a new Activity is being created, this handler method will be called
     * to retrieve a new Content object for the Activity.
     *
     * Called in these views:
     * - new
     *
     * @param Location $location
     * @param Campaign $campaign
     * @return null
     */
    public function createContent(Location $location = null, Campaign $campaign = null)
    {
        return null;
    }

    /**
     * Overwrite this method to return an existing Activity Content object which
     * would be displayed in a view.
     *
     * Called in these views:
     * - edit
     * - editModal
     * - read
     *
     * @param Location $location
     * @param Operation $operation
     * @return null
     */
    public function getContent(Location $location = null, Operation $operation)
    {
        if($operation) {
            $status = $this->contentService->getSocialMediaScheduleByOperation($operation);

            return $status;
        }

        return null;
    }

    /**
     * Implement this method to change the data of an Activity as per the form
     * data that has been posted in a view.
     *
     * Called in these views:
     * - new
     *
     * @param Activity $activity
     * @param $data Form submit data of the Activity.
     * @return Activity
     */
    public function processActivity(Activity $activity, $data)
    {
        return $activity;
    }

    /**
     * Modifies the Location of the Activity.
     *
     * Called in these views:
     * - new
     *
     * @param Location $location The Activity's Location.
     * @return Location
     */
    public function processActivityLocation(Location $location = null)
    {
        return $location;
    }

    /**
     * After a new Activity was created, this method makes it possible to alter
     * the data of the Content's Location (not the Activity's Location!) as per
     * the data provided for the Content.
     *
     * Called in these views:
     * - new
     *
     * @param Location $location Location of the Content.
     * @param $data Form submit data of the Content.
     * @return Location
     */
    public function processContentLocation(Location $location, $data)
    {
        return $location;
    }

    /**
     * Create or modify the Content object from the form data.
     *
     * Called in these views:
     * - new
     * - editApi
     *
     * @param Operation $operation
     * @param $data Form submit data of the Content.
     * @return mixed
     */
    public function processContent(Operation $operation, $data)
    {
            if(is_array($data)) {
                // If the status has already been created, we modify its data.
                $status = $this->contentService->getSocialMediaScheduleByOperation($operation);
                // If data comes from API call, then Locations will not be
                // entities, but their IDs in an array.
                if(!$data['locations'] instanceof ArrayCollection){
                    $locations = $this->em
                        ->getRepository('CampaignChainCoreBundle:Location')
                        ->findById(array_values($data['locations']));
                    $locations = new ArrayCollection($locations);
                } else {
                    $locations = $data['locations'];
                }
                $status->setLocations($locations);
                $status->setMessage($data['message']);
            } else {
                $status = $data;
            }


        return $status;
    }

    public function processSingleContentMultiOperation(Activity $activity, Form $form)
    {
    }

    /**
    * Define custom template rendering options for the new view in this method
    * as an array. Here's a sample of such an array:
    *
    * array(
    *     'template' => 'foo_template::edit.html.twig',
    *     'vars' => array(
    *         'foo1' => $bar1,
    *         'foo2' => $bar2
    *         )
    *     );
    *
    * Called in these views:
    * - new
    *
    * @param Operation $operation
    * @return null
    */
    public function getNewRenderOptions(Operation $operation = null)
    {
        return null;
    }

    /**
     * @param Operation $operation
     * @param bool $isModal Modal view yes or no?
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Exception
     */
    public function readAction(Operation $operation, $isModal = false)
    {
    }

    /**
     * The Activity controller calls this method after the form was submitted
     * and the new activity was persisted.
     *
     * @param Activity $activity
     * @param $data
     */
    public function postFormSubmitNewEvent(Activity $activity, $data)
    {
    }

    /**
     * This event is being called after the new Activity and its related
     * content was persisted.
     *
     * Called in these views:
     * - new
     *
     * @param Operation $operation
     * @param Form $form
     * @param $content The Activity's content object.
     * @return null
     */
    public function postPersistNewEvent(Operation $operation, $content = null)
    {
        // Content to be published immediately?
        $this->publishNow($operation);
    }

    /**
     * This event is being called before the edit form data has been submitted.
     *
     * Called in these views:
     * - edit
     *
     * @param Operation $operation
     * @return null
     */
    public function preFormSubmitEditEvent(Operation $operation)
    {
        return null;
    }

    /**
     * This event is being called after the edited Activity and its related
     * content was persisted.
     *
     * Called in these views:
     * - edit
     *
     * @param Operation $operation
     * @param Form $form
     * @param $content The Activity's content object.
     * @return null
     */
    public function postPersistEditEvent(Operation $operation, $content = null)
    {
        // Content to be published immediately?
        $this->publishNow($operation);
    }

    /**
     * Define custom template rendering options for the edit view in this method
     * as an array. Here's a sample of such an array:
     *
     * array(
     *     'template' => 'foo_template::edit.html.twig',
     *     'vars' => array(
     *         'foo1' => $bar1,
     *         'foo2' => $bar2
     *         )
     *     );
     *
     * Called in these views:
     * - edit
     *
     * @param Operation $operation
     * @return null
     */
    public function getEditRenderOptions(Operation $operation)
    {
        return null;
    }

    /**
     * This event is being called before the editModal form data has been
     * submitted.
     *
     * Called in these views:
     * - editModal
     *
     * @param Operation $operation
     * @return null
     */
    public function preFormSubmitEditModalEvent(Operation $operation)
    {
        return null;
    }

    /**
     * Define custom template rendering options for editModal view as array.
     *
     * Called in these views:
     * - editModal
     *
     * @see AbstractActivityHandler::getEditRenderOptions()
     * @param Operation $operation
     * @return null
     */
    public function getEditModalRenderOptions(Operation $operation)
    {
        return null;
    }

    /**
     * Let's a handler implementation define whether the Content should be
     * displayed or processed in a specific view or not.
     *
     * Called in these views:
     * - new
     * - edit
     * - editModal
     * - editApi
     *
     * @param $view
     * @return bool
     */
    public function hasContent($view)
    {
        return true;
    }

    private function publishNow(Operation $operation)
    {
        if ($this->schedulerUtil->isDueNow($operation->getStartDate())) {
            $this->job->execute($operation->getId());
            $content = $this->contentService->getSocialMediaScheduleByOperation($operation);
            foreach($content->getLocations() as $location){
                $flashMsgPart =
                    '<li><a href="'.$location->getUrl().'">View it on '
                    .$location->getName()
                    .' ('
                    .$location->getLocationModule()->getDisplayName()
                    .')</a></li>';
            }
            $this->session->getFlashBag()->add(
                'success',
                'The message was published on these Locations:'
                .'<ul>'
                .$flashMsgPart
                .'</ul>'
            );

            return true;
        }

        return false;
    }
}