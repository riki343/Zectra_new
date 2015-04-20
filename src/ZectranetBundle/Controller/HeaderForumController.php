<?php

namespace ZectranetBundle\Controller;

use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use ZectranetBundle\Entity\EntityOperations;
use ZectranetBundle\Entity\ForgotPassword;
use ZectranetBundle\Entity\HFHeader;
use ZectranetBundle\Entity\HFForum;
use ZectranetBundle\Entity\HFSubHeader;
use ZectranetBundle\Entity\HFThread;
use ZectranetBundle\Entity\HFThreadPost;
use ZectranetBundle\Entity\User;

class HeaderForumController extends Controller {
    /**
     * @Security("has_role('ROLE_USER')")
     * @param int $project_id
     * @return Response
     */
    public function indexAction($project_id) {
        $forum = $this->getDoctrine()->getRepository('ZectranetBundle:HFForum')->find($project_id);
        return $this->render('@Zectranet/headerForum.html.twig', array('forum' => $forum));
    }

    /**
     * @Security("has_role('ROLE_USER')")
     * @param int $project_id
     * @param int $subheader_id
     * @return Response
     */
    public function forumAction($project_id, $subheader_id) {
        /** @var HFForum $forum */
        $forum = $this->getDoctrine()->getRepository('ZectranetBundle:HFForum')->find($project_id);
        /** @var HFSubHeader $subheader */
        $subheader = $this->getDoctrine()->getRepository('ZectranetBundle:HFSubHeader')->find($subheader_id);
        return $this->render('@Zectranet/headerForumSubHeader.html.twig', array(
            'forum' => $forum,
            'sub' => $subheader,
        ));
    }

    /**
     * @Security("has_role('ROLE_USER')")
     * @param int $project_id
     * @return Response
     */
    public function settingsAction($project_id) {
        $forum = $this->getDoctrine()->getRepository('ZectranetBundle:HFForum')->find($project_id);
        return $this->render('@Zectranet/headerForumSettings.html.twig', array('forum' => $forum));
    }

    /**
     * @Security("has_role('ROLE_USER')")
     * @param int $project_id
     * @return JsonResponse
     */
    public function getHeadersAction($project_id) {
        $forum = $this->getDoctrine()->getRepository('ZectranetBundle:HFForum')->find($project_id);
        return new JsonResponse(EntityOperations::arrayToJsonArray($forum->getHeaders()));
    }

    /**
     * @Security("has_role('ROLE_USER')")
     * @param Request $request
     * @param int $project_id
     * @return JsonResponse
     */
    public function addNewHeaderAction(Request $request, $project_id) {
        $data = json_decode($request->getContent(), true);
        $params = array(
            'title' => $data['header']['title'],
            'bgColor' => $data['header']['bgColor'],
            'textColor' => $data['header']['textColor'],
        );
        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();
        try {
            HFHeader::addNewHeader($em, $project_id, $params);
        } catch (\Exception $ex) {
            $from = "Class: HFHeader, function: addNewHeader";
            $this->get('zectranet.errorlogger')->registerException($ex, $from);
            return new JsonResponse(false);
        }

        $forum = $this->getDoctrine()->getRepository('ZectranetBundle:HFForum')->find($project_id);
        return new JsonResponse(EntityOperations::arrayToJsonArray($forum->getHeaders()));
    }

    /**
     * @Security("has_role('ROLE_USER')")
     * @param Request $request
     * @param int $header_id
     * @return JsonResponse
     */
    public function addNewSubHeaderAction(Request $request, $header_id) {
        $data = json_decode($request->getContent(), true);
        $params = array(
            'title' => $data['subheader']['title'],
            'header_id' => $header_id,
            'description' => $data['subheader']['description'],
            'admin' => $data['subheader']['admin'],
        );
        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();
        $header = $em->getRepository('ZectranetBundle:HFHeader')->find($header_id);
        try {
            HFHeader::addNewSubHeader($em, $params);
        } catch (\Exception $ex) {
            $from = "Class: HFHeader, function: addNewSubHeader";
            $this->get('zectranet.errorlogger')->registerException($ex, $from);
            return new JsonResponse(false);
        }
        return new JsonResponse(EntityOperations::arrayToJsonArray($header->getForum()->getHeaders()));
    }

    /**
     * @Security("has_role('ROLE_USER')")
     * @param int $header_id
     * @return JsonResponse
     */
    public function deleteHeaderAction($header_id) {
        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();
        $header = $em->getRepository('ZectranetBundle:HFHeader')->find($header_id);
        $forum = $header->getForum();
        try {
            HFHeader::deleteHeader($em, $header_id);
        } catch (\Exception $ex) {
            $from = "Class: HFHeader, function: deleteHeader";
            $this->get('zectranet.errorlogger')->registerException($ex, $from);
            return new JsonResponse(false);
        }

        return new JsonResponse(EntityOperations::arrayToJsonArray($forum->getHeaders()));
    }

    /**
     * @Security("has_role('ROLE_USER')")
     * @param int $project_id
     * @param int $subheader_id
     * @param int $thread_id
     * @return Response
     */
    public function showThreadAction($project_id, $subheader_id, $thread_id) {
        $forum = $this->getDoctrine()->getRepository('ZectranetBundle:HFForum')->find($project_id);
        $subHeader = $this->getDoctrine()->getRepository('ZectranetBundle:HFSubHeader')->find($subheader_id);
        $thread = $this->getDoctrine()->getRepository('ZectranetBundle:HFThread')->find($thread_id);

        return $this->render('@Zectranet/headerForumThread.html.twig', array(
            'forum' => $forum,
            'sub' => $subHeader,
            'thread' => $thread,
        ));
    }

    /**
     * @param Request $request
     * @param int $subheader_id
     * @return Response
     */
    public function startNewThreadAction(Request $request, $subheader_id) {
        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();
        $subHeader = $em->getRepository('ZectranetBundle:HFSubHeader')->find($subheader_id);
        /** @var User $user */
        $user = $this->getUser();
        $params = array(
            'title' => $request->request->get('title'),
            'message' => $request->request->get('message'),
            'keywords' => $request->request->get('keywords'),
        );

        $thread = null;
        try {
            $thread = HFThread::startNewThread($em, $subheader_id, $user->getId(), $params);
        } catch (\Exception $ex) {
            $from = "Class: HFThread, function: startNewThread";
            $this->get('zectranet.errorlogger')->registerException($ex, $from);
            return $this->redirectToRoute('zectranet_show_header_forum_subheader', array(
                'project_id' => $subHeader->getHeader()->getForumID(),
                'subheader_id' => $subheader_id,
            ));
        }

        return $this->redirectToRoute('zectranet_show_header_forum_thread', array(
            'project_id' => $subHeader->getHeader()->getForumID(),
            'subheader_id' => $subHeader->getId(),
            'thread_id' => $thread->getId(),
        ));
    }

    /**
     * @param Request $request
     * @param int $thread_id
     * @return RedirectResponse
     */
    public function addNewPostAction(Request $request, $thread_id) {
        $message = $request->request->get('message');
        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();
        /** @var User $user */
        $user = $this->getUser();
        $thread = $em->getRepository('ZectranetBundle:HFThread')->find($thread_id);
        try {
            $th = HFThreadPost::addNewPost($em, $thread_id, $user->getId(), $message);
        } catch (\Exception $ex) {
            $from = "Class: HFThreadPost, function: addNewPost";
            $this->get('zectranet.errorlogger')->registerException($ex, $from);
        }
        return $this->redirectToRoute('zectranet_show_header_forum_thread', array(
            'project_id' => $thread->getSubHeader()->getHeader()->getForumID(),
            'subheader_id' => $thread->getSubHeaderID(),
            'thread_id' => $thread->getId(),
        ));
    }

    /**
     * @param int $project_id
     * @return JsonResponse
     */
    public function getProjectSettingsInfoAction($project_id) {
        $info = array();
        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();
        /** @var User $user */
        $user = $this->getUser();
        $info['HO_Contacts'] = HFForum::getNotProjectHomeOfficeMembers($em, $user->getId(), $project_id);
        $info['All_Contacts'] = HFForum::getNotProjectSiteMembers($em, $project_id);
        $info['Project_Team'] = EntityOperations::arrayToJsonArray(
            $em->getRepository('ZectranetBundle:Request')->findBy(array('projectid' => $project_id))
        );
        return new JsonResponse($info);
    }

    /**
     * @param Request $request
     * @param int $project_id
     * @return JsonResponse
     */
    public function sendRequestAction(Request $request, $project_id) {
        $data = json_decode($request->getContent(), true);
        $user_id = $data['user_id'];
        $message = $data['message'];
        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();
        /** @var User $user */
        $user = $this->getUser();
        $contact = $em->find('ZectranetBundle:User', $user_id);
        try {
            HFForum::sendRequestToUser($em, $user_id, $project_id, $message, $user->getId());
        } catch (\Exception $ex) {
            $from = 'class: HFForum, function: sendRequestToUser';
            $this->get('zectranet.errorlogger')->registerException($ex, $from);
            return new JsonResponse(-1);
        }
        $logMessage = 'User "' . $user->getUsername() . '" sent project request to user "'
            . $contact->getUsername() . '"';
        $this->get('zectranet.projectlogger')->logEvent($logMessage, $project_id, 2);
        return new JsonResponse(1);
    }

    /**
     * @param Request $request
     * @param int $project_id
     * @return JsonResponse
     */
    public function removeUserAction(Request $request, $project_id) {
        $data = json_decode($request->getContent(), true);
        $user_id = $data['user_id'];
        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();
        /** @var User $user */
        $user = $this->getUser();
        $contact = $em->find('ZectranetBundle:User', $user_id);
        try {
            HFForum::removeUserFromProject($em, $user_id, $project_id);
        } catch (\Exception $ex) {
            $from = 'class: HFForum, function: removeUserFromProject';
            $this->get('zectranet.errorlogger')->registerException($ex, $from);
            return new JsonResponse(-1);
        }
        $logMessage = 'User "' . $user->getUsername() . '" remove user "'
            . $contact->getUsername() . '" from project';
        $this->get('zectranet.projectlogger')->logEvent($logMessage, $project_id, 2);
        return new JsonResponse(1);
    }
}