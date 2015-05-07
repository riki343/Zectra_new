<?php

namespace ZectranetBundle\Controller;

use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use ZectranetBundle\Entity\HFForum;
use ZectranetBundle\Entity\Office;
use ZectranetBundle\Entity\Project;
use ZectranetBundle\Entity\QnAForum;
use ZectranetBundle\Entity\Task;
use ZectranetBundle\Entity\User;

class SearchController extends Controller {
    /**
     * @Security("has_role('ROLE_USER')")
     * @param Request $request
     * @return JsonResponse
     */
    public function MiniSearchAction(Request $request) {
        $data = json_decode($request->getContent(), true);
        $slug = $data['slug'];
        $task = $data['task'];
        /** @var User $user */
        $user = $this->getUser();
        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();
        $result = null;
        if ($task) {
            $result = array(
                'homeOffice' => array(),
                'HFForums' => array(),
                'QnAForums' => array(),
                'reserved' => array(),
                'Projects' => array(),
                'Tasks' => array(),
            );
        } else {
            $result = array(
                'homeOffice' => Office::searchHomeOffice($em, $user->getHomeOfficeID(), $slug, 3),
                'HFForums' => HFForum::searchHFForums($user->getConnectedHFForums(), $slug, 3),
                'QnAForums' => QnAForum::searchQnAForums($user->getConnectedQnAForums(), $slug, 3),
                'reserved' => array(),
                'Projects' => Project::searchProjects($user->getProjects(), $slug),
                'Tasks' => array(),
            );
        }
        return new JsonResponse($result);
    }

    /**
     * @param null $slug
     * @return Response
     */
    public function SearchAction($slug = null) {
        return $this->render('@Zectranet/search.html.twig');
    }
}